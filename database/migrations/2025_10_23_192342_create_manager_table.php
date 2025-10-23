    <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('manager', function (Blueprint $table) {
            $table->char('account', 30)->primary()->comment('管理者帳號');
            $table->string('password', 45)->nullable()->comment('管理者密碼');
            $table->char('name', 5)->nullable()->comment('管理者姓名');
            $table->string('email', 45)->nullable()->comment('管理者Email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager');
    }
};
