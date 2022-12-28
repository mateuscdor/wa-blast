<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('conversation_id');
            $table->bigInteger('user_id')->nullable();
            $table->string('message_id')->nullable();
            $table->enum('number_type', ['RECEIVER', 'SENDER', 'BROADCAST', 'AUTO_REPLY'])->default('RECEIVER');
            $table->enum('read_status', ['PENDING', 'DELIVERED', 'READ', 'UNREAD'])->default('UNREAD');
            $table->text('message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats');
    }
}
