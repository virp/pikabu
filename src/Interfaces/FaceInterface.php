<?php

declare(strict_types=1);

namespace Pikabu\Interfaces;

interface FaceInterface
{
    /**
     * Returns face id or 0, if face is new
     */
    public function getId(): int;

    /**
     * Returns race parameter: from 0 to 100.
     */
    public function getRace(): int;

    /**
     * Returns face emotion level: from 0 to 1000.
     */
    public function getEmotion(): int;

    /**
     * Returns face oldness level: from 0 to 1000.
     */
    public function getOldness(): int;
}
