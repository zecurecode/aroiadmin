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
        // Update apningstid table to match exact schema
        if (Schema::hasTable('apningstid')) {
            Schema::table('apningstid', function (Blueprint $table) {
                // Add missing columns if they don't exist
                if (! Schema::hasColumn('apningstid', 'userid')) {
                    $table->integer('userid')->after('id');
                }
                if (! Schema::hasColumn('apningstid', 'opensteinkjer')) {
                    $table->string('opensteinkjer', 11)->default('');
                }
                if (! Schema::hasColumn('apningstid', 'closesteinkjer')) {
                    $table->string('closesteinkjer', 11)->default('');
                }
                if (! Schema::hasColumn('apningstid', 'notessteinkjer')) {
                    $table->string('notessteinkjer', 11)->default('');
                }
                if (! Schema::hasColumn('apningstid', 'statussteinkjer')) {
                    $table->integer('statussteinkjer')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btbsteinkjer')) {
                    $table->integer('btbsteinkjer')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btnmoan')) {
                    $table->integer('btnmoan')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btnlade')) {
                    $table->integer('btnlade')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btngramyra')) {
                    $table->integer('btngramyra')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btnnamsos')) {
                    $table->integer('btnnamsos')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btnfrosta')) {
                    $table->integer('btnfrosta')->default(0);
                }
                if (! Schema::hasColumn('apningstid', 'btnhell')) {
                    $table->integer('btnhell')->default(0);
                }

                // Drop timestamps if they exist (original table doesn't have them)
                if (Schema::hasColumn('apningstid', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        } else {
            // Create apningstid table from scratch
            Schema::create('apningstid', function (Blueprint $table) {
                $table->id();
                $table->integer('userid');
                $table->string('day', 11)->nullable();
                $table->string('openlade', 11)->nullable();
                $table->string('closelade', 11)->nullable();
                $table->string('noteslade', 100)->nullable();
                $table->string('openmoan', 11)->default('');
                $table->string('closemoan', 11)->default('');
                $table->string('notesmoan', 11)->default('');
                $table->string('opennamsos', 11)->default('');
                $table->string('closenamsos', 11)->default('');
                $table->string('notesnamsos', 11)->default('');
                $table->string('opengramyra', 11)->default('');
                $table->string('closegramyra', 11)->default('');
                $table->string('notesgramyra', 11)->default('');
                $table->string('openfrosta', 11)->default('');
                $table->string('closefrosta', 11)->default('');
                $table->string('notesfrosta', 11)->default('');
                $table->string('openhell', 11)->default('');
                $table->string('closehell', 11)->default('');
                $table->string('noteshell', 100)->default('');
                $table->integer('statuslade')->default(0);
                $table->integer('statusmoan')->default(0);
                $table->integer('statusgramyra')->default(0);
                $table->integer('statusnamsos')->default(0);
                $table->integer('statusfrosta')->default(0);
                $table->integer('btnmoan')->default(0);
                $table->integer('btnlade')->default(0);
                $table->integer('btngramyra')->default(0);
                $table->integer('btnnamsos')->default(0);
                $table->integer('btnfrosta')->default(0);
                $table->integer('statushell')->default(0);
                $table->integer('btnhell')->default(0);
                $table->string('opensteinkjer', 11)->default('');
                $table->string('closesteinkjer', 11)->default('');
                $table->string('notessteinkjer', 11)->default('');
                $table->integer('statussteinkjer')->default(0);
                $table->integer('btbsteinkjer')->default(0);
            });
        }

        // Create avdeling table if it doesn't exist
        if (! Schema::hasTable('avdeling')) {
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

        // Create leveringstid table if it doesn't exist
        if (! Schema::hasTable('leveringstid')) {
            Schema::create('leveringstid', function (Blueprint $table) {
                $table->id();
                $table->string('tid', 11);
            });
        }

        // Create mail table if it doesn't exist
        if (! Schema::hasTable('mail')) {
            Schema::create('mail', function (Blueprint $table) {
                $table->id();
                $table->text('text');
            });
        }

        // Update orders table to match exact schema
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                // Add missing columns if they don't exist
                if (! Schema::hasColumn('orders', 'wcstatus')) {
                    $table->string('wcstatus', 50)->default('');
                }
                if (! Schema::hasColumn('orders', 'payref')) {
                    $table->string('payref', 200)->default('');
                }
                if (! Schema::hasColumn('orders', 'seordre')) {
                    $table->integer('seordre')->default(0);
                }
                if (! Schema::hasColumn('orders', 'paymentmethod')) {
                    $table->string('paymentmethod', 50)->default('');
                }
                if (! Schema::hasColumn('orders', 'hentes')) {
                    $table->string('hentes', 50)->default('');
                }

                // Modify existing columns to match schema
                $table->string('ordrestatus', 5)->default('1')->change();
                $table->string('curl', 150)->default('')->change();

                // Drop Laravel timestamps if they exist (original table doesn't have them)
                if (Schema::hasColumn('orders', 'created_at')) {
                    $table->dropColumn(['created_at', 'updated_at']);
                }
            });
        }

        // Create overstyr table if it doesn't exist
        if (! Schema::hasTable('overstyr')) {
            Schema::create('overstyr', function (Blueprint $table) {
                $table->id();
                $table->integer('vognid');
                $table->integer('status');
                $table->timestamp('timestamp')->useCurrent();
            });
        }

        // Create _apningstid table if it doesn't exist
        if (! Schema::hasTable('_apningstid')) {
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

        // Create _avdeling table if it doesn't exist
        if (! Schema::hasTable('_avdeling')) {
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This down method is intentionally minimal to avoid data loss
        // Only drop tables that didn't exist before this migration
        Schema::dropIfExists('_avdeling');
        Schema::dropIfExists('_apningstid');
        Schema::dropIfExists('overstyr');
        Schema::dropIfExists('mail');
        Schema::dropIfExists('leveringstid');
        Schema::dropIfExists('avdeling');

        // For existing tables, we don't drop them to avoid data loss
        // You would need to manually revert column changes if needed
    }
};
