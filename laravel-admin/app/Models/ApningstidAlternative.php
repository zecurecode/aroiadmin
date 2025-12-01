<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApningstidAlternative extends Model
{
    use HasFactory;

    protected $table = '_apningstid';

    public $timestamps = false; // The original table doesn't have timestamps

    protected $primaryKey = 'AvdID';

    public $incrementing = false; // AvdID is not auto-incrementing

    protected $fillable = [
        'AvdID',
        'Navn',
        'Telefon',
        'ManStart',
        'ManStopp',
        'ManStengt',
        'TirStart',
        'TirStopp',
        'TirStengt',
        'OnsStart',
        'OnsStopp',
        'OnsStengt',
        'TorStart',
        'TorStopp',
        'TorStengt',
        'FreStart',
        'FreStopp',
        'FreStengt',
        'LorStart',
        'LorStopp',
        'LorStengt',
        'SonStart',
        'SonStopp',
        'SonStengt',
        'StengtMelding',
        'SesongStengt',
        'url',
    ];

    protected $casts = [
        'AvdID' => 'integer',
        'ManStengt' => 'integer',
        'TirStengt' => 'integer',
        'OnsStengt' => 'integer',
        'TorStengt' => 'integer',
        'FreStengt' => 'integer',
        'LorStengt' => 'integer',
        'SonStengt' => 'integer',
        'SesongStengt' => 'integer',
    ];

    /**
     * Get opening hours for a specific day.
     */
    public function getHoursForDay($day)
    {
        $dayMapping = [
            'monday' => ['ManStart', 'ManStopp', 'ManStengt'],
            'tuesday' => ['TirStart', 'TirStopp', 'TirStengt'],
            'wednesday' => ['OnsStart', 'OnsStopp', 'OnsStengt'],
            'thursday' => ['TorStart', 'TorStopp', 'TorStengt'],
            'friday' => ['FreStart', 'FreStopp', 'FreStengt'],
            'saturday' => ['LorStart', 'LorStopp', 'LorStengt'],
            'sunday' => ['SonStart', 'SonStopp', 'SonStengt'],
        ];

        $dayKey = strtolower($day);
        if (! isset($dayMapping[$dayKey])) {
            return null;
        }

        $fields = $dayMapping[$dayKey];

        return [
            'start' => $this->{$fields[0]},
            'stop' => $this->{$fields[1]},
            'closed' => $this->{$fields[2]},
        ];
    }

    /**
     * Check if open on specific day.
     */
    public function isOpenOnDay($day)
    {
        $hours = $this->getHoursForDay($day);

        return $hours && ! $hours['closed'] && ! $this->SesongStengt;
    }

    /**
     * Get all week hours.
     */
    public function getWeekHours()
    {
        return [
            'monday' => $this->getHoursForDay('monday'),
            'tuesday' => $this->getHoursForDay('tuesday'),
            'wednesday' => $this->getHoursForDay('wednesday'),
            'thursday' => $this->getHoursForDay('thursday'),
            'friday' => $this->getHoursForDay('friday'),
            'saturday' => $this->getHoursForDay('saturday'),
            'sunday' => $this->getHoursForDay('sunday'),
        ];
    }

    /**
     * Check if closed for season.
     */
    public function isSeasonClosed()
    {
        return $this->SesongStengt == 1;
    }
}
