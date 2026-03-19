import asyncio
import re
import json
import httpx
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from bs4 import BeautifulSoup
from typing import List

app = FastAPI()

# --- 資料模型定義 ---

class ScheduleRequest(BaseModel):
    year: int = 114
    seme: int = 2
    category: str = "B"
    building: str = "GC,綜三館"
    classrooms: List[str]  # 接收字串列表，例如 ["BGC0513,BGC0513-生物資訊實驗室"]

# --- 邏輯處理 ---

BASE_URL = "https://m.nfu.edu.tw/plab/"

def parse_html(html: str):
    """解析 HTML 並回傳濃縮後的字典結構"""
    try:
        soup = BeautifulSoup(html, 'lxml')
        title_div = soup.find('div', class_='nonfocal')
        if not title_div: return None
        
        meta = re.search(r'(\d+).+?(\d+).+?/ ([A-Z0-9]+)', title_div.get_text())
        day_map = {"一": 1, "二": 2, "三": 3, "四": 4, "五": 5}
        
        records = []
        for zh, num in day_map.items():
            tab = soup.find('div', id=lambda x: x and f"{zh}-" in x and "tabbody" in x)
            if not tab: continue
            
            for item in tab.find_all('li', class_='result'):
                tds = [td.get_text(strip=True) for td in item.find_all('td')]
                d = {"d": num}
                for r in tds:
                    if "科目" in r: d["n"] = r.split("：")[-1]
                    elif "班級" in r: d["c"] = r.split("：")[-1]
                    elif "教師" in r: d["i"] = r.split("：")[-1]
                    elif "節次" in r: d["p"] = [int(p) for p in r.split("：")[-1].split(',') if p.isdigit()]
                records.append(d)

        return {
            "y": int(meta.group(1)),
            "s": int(meta.group(2)),
            "cid": meta.group(3),
            "r": records
        }
    except Exception:
        return None

async def fetch_task(client, req: ScheduleRequest, classroom_str: str, token: str):
    payload = {
        'year': req.year,
        'seme': req.seme,
        'category': req.category,
        'building': req.building,
        'classroom': classroom_str,
        'anticsrf': token,
        'submit': '查詢'
    }
    try:
        resp = await client.post(f"{BASE_URL}result", data=payload)
        return parse_html(resp.text)
    except Exception:
        return None

# --- API 端點 ---

@app.post("/batch_schedule")
async def get_batch_schedule(req: ScheduleRequest):
    # 使用 verify=False 跳過 SSL 驗證（針對學校舊式憑證）
    async with httpx.AsyncClient(verify=False, timeout=20.0) as client:
        # 1. 取得一次性 Token
        try:
            landing = await client.get(BASE_URL)
            soup = BeautifulSoup(landing.text, 'lxml')
            token = soup.find('input', {'name': 'anticsrf'})['value']
        except Exception:
            raise HTTPException(status_code=500, detail="無法取得學校伺服器連線")

        # 2. 建立併發任務
        tasks = [fetch_task(client, req, c, token) for c in req.classrooms]
        results = await asyncio.gather(*tasks)
        
        # 3. 過濾無效結果
        final_data = [r for r in results if r is not None]
        return final_data

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)