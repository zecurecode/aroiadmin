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
        // Create leveringstid table - URGENT for live app
        if (!Schema::hasTable('leveringstid')) {
            Schema::create('leveringstid', function (Blueprint $table) {
                $table->id();
                $table->string('tid', 11);
            });
        }

        // Create avdeling table
        if (!Schema::hasTable('avdeling')) {
            Schema::create('avdeling', function (Blueprint $table) {
                $table->id();
                $table->string('navn', 30);
                $table->string('tlf', 10);
                $table->string('geo', 50);
                $table->integer('siteid');
                $table->boolean('inaktivert');
                $table->string('deaktivert_tekst', 11);
                $table->string('url', 200);
            });
        }

        // Create mail table
        if (!Schema::hasTable('mail')) {
            Schema::create('mail', function (Blueprint $table) {
                $table->id();
                $table->text('text');
            });
        }

        // Create overstyr table
        if (!Schema::hasTable('overstyr')) {
            Schema::create('overstyr', function (Blueprint $table) {
                $table->id();
                $table->integer('vognid');
                $table->integer('status');
                $table->timestamp('timestamp')->useCurrent();
            });
        }

        // Create _apningstid table
        if (!Schema::hasTable('_apningstid')) {
            Schema::create('_apningstid', function (Blueprint $table) {
                $table->integer('AvdID')->primary();
                $table->string('Navn', 100);
                $table->string('Telefon', 15);
                $table->string('ManStart', 11);
                $table->string('ManStopp', 11);
                $table->integer('ManStengt');
                $table->string('TirStart', 11);
                $table->string('TirStopp', 11);
                $table->integer('TirStengt');
                $table->string('OnsStart', 11);
                $table->string('OnsStopp', 11);
                $table->integer('OnsStengt');
                $table->string('TorStart', 11);
                $table->string('TorStopp', 11);
                $table->integer('TorStengt');
                $table->string('FreStart', 11);
                $table->string('FreStopp', 11);
                $table->integer('FreStengt');
                $table->string('LorStart', 11);
                $table->string('LorStopp', 11);
                $table->integer('LorStengt');
                $table->string('SonStart', 11);
                $table->string('SonStopp', 11);
                $table->integer('SonStengt');
                $table->string('StengtMelding', 100);
                $table->integer('SesongStengt');
                $table->string('url', 256);
            });
        }

        // Create _avdeling table
        if (!Schema::hasTable('_avdeling')) {
            Schema::create('_avdeling', function (Blueprint $table) {
                $table->id('Id');
                $table->string('Navn', 50);
                $table->string('Tlf', 10);
                $table->string('Epost', 50);
                $table->integer('SiteID');
                $table->boolean('Aktiv');
                $table->string('Url', 150);
                $table->string('APIKey', 250);
                $table->string('APISecret', 250);
                $table->string('APIUrl', 250);
            });
        }

        // Update apningstid table with missing columns if needed
        if (Schema::hasTable('apningstid')) {
            Schema::table('apningstid', function (Blueprint $table) {
                if (!Schema::hasColumn('apningstid', 'userid')) {
                    $table->integer('userid')->default(0)->after('id');
                }
                if (!Schema::hasColumn('apningstid', 'opensteinkjer')) {
                    $table->string('opensteinkjer', 11)->default('');
                }
                if (!Schema::hasColumn('apningstid', 'closesteinkjer')) {
                    $table->string('closesteinkjer', 11)->default('');
                }
                if (!Schema::hasColumn('apningstid', 'notessteinkjer')) {
                    $table->string('notessteinkjer', 11)->default('');
                }
                if (!Schema::hasColumn('apningstid', 'statussteinkjer')) {
                    $table->integer('statussteinkjer')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btbsteinkjer')) {
                    $table->integer('btbsteinkjer')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btnmoan')) {
                    $table->integer('btnmoan')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btnlade')) {
                    $table->integer('btnlade')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btngramyra')) {
                    $table->integer('btngramyra')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btnnamsos')) {
                    $table->integer('btnnamsos')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btnfrosta')) {
                    $table->integer('btnfrosta')->default(0);
                }
                if (!Schema::hasColumn('apningstid', 'btnhell')) {
                    $table->integer('btnhell')->default(0);
                }
            });
        }

        // Update orders table with missing columns if needed
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'wcstatus')) {
                    $table->string('wcstatus', 50)->default('');
                }
                if (!Schema::hasColumn('orders', 'payref')) {
                    $table->string('payref', 200)->default('');
                }
                if (!Schema::hasColumn('orders', 'seordre')) {
                    $table->integer('seordre')->default(0);
                }
                if (!Schema::hasColumn('orders', 'paymentmethod')) {
                    $table->string('paymentmethod', 50)->default('');
                }
                if (!Schema::hasColumn('orders', 'hentes')) {
                    $table->string('hentes', 50)->default('');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_avdeling');
        Schema::dropIfExists('_apningstid');
        Schema::dropIfExists('overstyr');
        Schema::dropIfExists('mail');
        Schema::dropIfExists('avdeling');
        Schema::dropIfExists('leveringstid');
    }
};
