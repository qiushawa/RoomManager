import argparse
import asyncio
import json
import re
import sys
from typing import List, Optional

import httpx
from bs4 import BeautifulSoup

# --- 邏輯處理 ---

BASE_URL = "https://m.nfu.edu.tw/plab/"

DEFAULT_HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language": "zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7",
}


def parse_html(html: str):
    """解析 HTML 並回傳濃縮後的字典結構"""
    try:
        soup = BeautifulSoup(html, 'lxml')
        title_div = soup.find('div', class_='nonfocal')
        if not title_div:
            return None

        meta = re.search(r'(\d+).+?(\d+).+?/ ([A-Z0-9]+)', title_div.get_text())
        day_map = {"一": 1, "二": 2, "三": 3, "四": 4, "五": 5}

        records = []
        for zh, num in day_map.items():
            tab = soup.find('div', id=lambda x: x and f"{zh}-" in x and "tabbody" in x)
            if not tab:
                continue

            for item in tab.find_all('li', class_='result'):
                tds = [td.get_text(strip=True) for td in item.find_all('td')]
                d = {"d": num}
                for r in tds:
                    if "科目" in r:
                        d["n"] = r.split("：")[-1]
                    elif "班級" in r:
                        d["c"] = r.split("：")[-1]
                    elif "教師" in r:
                        d["i"] = r.split("：")[-1]
                    elif "節次" in r:
                        d["p"] = [int(p) for p in r.split("：")[-1].split(',') if p.isdigit()]
                records.append(d)

        return {
            "y": int(meta.group(1)),
            "s": int(meta.group(2)),
            "cid": meta.group(3),
            "r": records,
        }
    except Exception:
        return None


async def fetch_task(client, payload: dict, classroom_str: str, token: str):
    payload = {
        'year': payload['year'],
        'seme': payload['seme'],
        'category': payload['category'],
        'building': payload['building'],
        'classroom': classroom_str,
        'anticsrf': token,
        'submit': '查詢',
    }
    try:
        resp = await client.post(f"{BASE_URL}result", data=payload)
        return parse_html(resp.text)
    except Exception:
        return None


async def get_batch_schedule(payload: dict):
    # 使用 verify=False 跳過 SSL 驗證（針對學校舊式憑證）
    async with httpx.AsyncClient(
        verify=False,
        timeout=20.0,
        headers=DEFAULT_HEADERS,
        follow_redirects=True,
        trust_env=True,
    ) as client:
        # 1. 取得一次性 Token
        try:
            landing = await client.get(BASE_URL)
            soup = BeautifulSoup(landing.text, 'lxml')
            token = soup.find('input', {'name': 'anticsrf'})['value']
        except Exception as exc:
            raise RuntimeError(f"無法取得學校伺服器連線: {type(exc).__name__}: {exc}") from exc

        # 2. 建立併發任務
        tasks = [fetch_task(client, payload, c, token) for c in payload['classrooms']]
        results = await asyncio.gather(*tasks)

        # 3. 過濾無效結果
        return [r for r in results if r is not None]


def parse_args(argv: Optional[List[str]] = None) -> dict:
    parser = argparse.ArgumentParser(description="NFU schedule import CLI")
    parser.add_argument("--year", type=int, default=114)
    parser.add_argument("--seme", type=int, default=2)
    parser.add_argument("--category", type=str, default="B")
    parser.add_argument("--building", type=str, default="GC,綜三館")
    parser.add_argument("--classroom", action="append", default=[])
    parser.add_argument("--input-json", action="store_true", help="Read JSON payload from stdin")

    args = parser.parse_args(argv)

    if args.input_json:
        raw = sys.stdin.read().strip()
        if not raw:
            raise ValueError("stdin is empty")
        payload = json.loads(raw)
        if not isinstance(payload, dict):
            raise ValueError("payload must be an object")
        return payload

    classrooms = args.classroom or []
    payload = {
        "year": args.year,
        "seme": args.seme,
        "category": args.category,
        "building": args.building,
        "classrooms": classrooms,
    }

    return payload


def validate_payload(payload: dict) -> dict:
    required = ["year", "seme", "category", "building", "classrooms"]
    for key in required:
        if key not in payload:
            raise ValueError(f"missing field: {key}")

    year = payload.get("year")
    seme = payload.get("seme")
    category = payload.get("category")
    building = payload.get("building")
    classrooms = payload.get("classrooms")

    if not isinstance(year, int):
        raise ValueError("year must be an int")

    if not isinstance(seme, int):
        raise ValueError("seme must be an int")

    if not isinstance(category, str) or not category.strip():
        raise ValueError("category must be a non-empty string")

    if not isinstance(building, str) or not building.strip():
        raise ValueError("building must be a non-empty string")

    if not isinstance(classrooms, list) or not classrooms:
        raise ValueError("classrooms must be a non-empty list")

    return payload


def main(argv: Optional[List[str]] = None) -> int:
    try:
        payload = parse_args(argv)
        payload = validate_payload(payload)
        result = asyncio.run(get_batch_schedule(payload))
        sys.stdout.write(json.dumps(result, ensure_ascii=False))
        return 0
    except Exception as exc:
        sys.stderr.write(str(exc))
        return 1


if __name__ == "__main__":
    raise SystemExit(main())