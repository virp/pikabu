<?php

declare(strict_types=1);

namespace Pikabu\Interfaces;

interface FaceFinderInterface
{
    /**
     * Finds 5 most similar faces in DB.
     * If the specified face is new (id=0),
     * then it will be saved to DB.
     *
     * @param  FaceInterface  $face  Face to find and save (if id=0)
     * @return FaceInterface[] List of 5 most similar faces,
     * including the searched one
     */
    public function resolve(FaceInterface $face): array;

    /**
     * Removes all faces in DB and (!) reset faces id sequence
     */
    public function flush(): void;
}
