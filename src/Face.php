<?php

declare(strict_types=1);

namespace Pikabu;

use http\Exception\RuntimeException;
use Pikabu\Interfaces\FaceInterface;

class Face implements FaceInterface
{
    const PARAMETER_RACE = 'Race';
    const PARAMETER_EMOTION = 'Emotion';
    const PARAMETER_OLDNESS = 'Oldness';

    /**
     * @var int
     */
    private $race;

    /**
     * @var int
     */
    private $emotion;

    /**
     * @var int
     */
    private $oldness;

    /**
     * @var int
     */
    private $id;

    /**
     * Face constructor.
     *
     * @param  int  $race
     * @param  int  $emotion
     * @param  int  $oldness
     * @param  int  $id
     */
    public function __construct(int $race, int $emotion, int $oldness, int $id = 0)
    {
        $this->checkParameterRange(self::PARAMETER_RACE, $race, 0, 100);
        $this->checkParameterRange(self::PARAMETER_EMOTION, $emotion, 0, 1000);
        $this->checkParameterRange(self::PARAMETER_OLDNESS, $oldness, 0, 1000);

        $this->race = $race;
        $this->emotion = $emotion;
        $this->oldness = $oldness;
        $this->id = $id;
    }

    /**
     * Returns face id or 0, if face is new
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns race parameter: from 0 to 100.
     */
    public function getRace(): int
    {
        return $this->race;
    }

    /**
     * Returns face emotion level: from 0 to 1000.
     */
    public function getEmotion(): int
    {
        return $this->emotion;
    }

    /**
     * Returns face oldness level: from 0 to 1000.
     */
    public function getOldness(): int
    {
        return $this->oldness;
    }

    /**
     * @param  string  $parameter
     * @param  int  $value
     * @param  int  $min
     * @param  int  $max
     */
    private function checkParameterRange(string $parameter, int $value, int $min, int $max): void
    {
        if ($value < $min || $value > $max) {
            throw new RuntimeException("Parameter `$parameter` out of range ($value), allowed min: $min, max: $max");
        }
    }
}
