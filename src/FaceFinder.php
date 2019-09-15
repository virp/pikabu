<?php

declare(strict_types=1);

namespace Pikabu;

use PDO;
use Pikabu\Interfaces\FaceFinderInterface;
use Pikabu\Interfaces\FaceInterface;

class FaceFinder implements FaceFinderInterface
{
    const DB_NAME = 'face_finder';
    const DB_FACES_TABLE = 'faces';
    const FACES_LIMIT = 10000;
    const SIMILAR_LIMIT = 5;

    /**
     * @var PDO
     */
    private $db;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var FaceInterface[]
     */
    private $faces;

    /**
     * FaceFinder constructor.
     *
     * @param  string  $username
     * @param  string  $password
     * @param  string  $host
     */
    public function __construct(string $username, string $password, string $host = 'localhost')
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;

        $this->initConnection();
    }

    /**
     * Finds 5 most similar faces in DB.
     * If the specified face is new (id=0),
     * then it will be saved to DB.
     *
     * @param  FaceInterface  $face  Face to find and save (if id=0)
     * @return FaceInterface[] List of 5 most similar faces,
     * including the searched one
     */
    public function resolve(FaceInterface $face): array
    {
        if (!$this->faces) {
            $this->faces = $this->getFaces();
        }

        $this->createFaceIfNeeded($face);

        $similarFaces = $this->getSimilarFaces($face, self::SIMILAR_LIMIT);

        $faces = [];

        foreach ($similarFaces as $similarFaceId) {
            $faces[] = $this->faces[$similarFaceId];
        }

        return $faces;
    }

    /**
     * Removes all faces in DB and (!) reset faces id sequence
     */
    public function flush(): void
    {
        $this->db->exec('truncate faces');
    }

    private function initConnection(): void
    {
        $this->initDb();
        $this->checkTable();
    }

    private function initDb(): void
    {
        $this->db = new PDO("mysql:host=$this->host", $this->username, $this->password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = $this->db->query(
            sprintf(
                "select count(schema_name) from information_schema.schemata where schema_name = '%s'",
                self::DB_NAME
            ),
            PDO::FETCH_COLUMN,
            0
        );

        if (!(int)$query->fetch()) {
            $this->createDb();
        }

        $this->db->exec('use '.self::DB_NAME);
    }

    private function createDb(): void
    {
        $this->db->exec(sprintf(
            'create database %s character set utf8mb4 collate utf8mb4_unicode_ci',
            self::DB_NAME
        ));
    }

    private function checkTable(): void
    {
        $query = $this->db->query(
            sprintf("show tables like '%s'", self::DB_FACES_TABLE),
            PDO::FETCH_COLUMN,
            0
        );

        if (!$query->fetch()) {
            $sql = sprintf(
                "create table %s ("
                ."id int unsigned auto_increment,"
                ."race int unsigned not null,"
                ."emotion int unsigned not null,"
                ."oldness int unsigned not null,"
                ."constraint table_name_pk primary key (id));",
                self::DB_FACES_TABLE
            );
            $this->db->exec($sql);
        }
    }

    /**
     * @param  FaceInterface  $face
     */
    private function createFaceIfNeeded(FaceInterface $face): void
    {
        if ($face->getId() !== 0) {
            return;
        }

        $this->db->exec(sprintf(
            "insert into %s (race, emotion, oldness) values (%s, %s, %s)",
            self::DB_FACES_TABLE,
            $face->getRace(),
            $face->getEmotion(),
            $face->getOldness()
        ));

        $id = (int)$this->db->lastInsertId();

        $this->faces[$id] = new Face(
            $face->getRace(),
            $face->getEmotion(),
            $face->getOldness(),
            $id
        );
    }

    /**
     * @return FaceInterface[]
     */
    private function getFaces(): array
    {
        $query = $this->db
            ->prepare("select * from ".self::DB_FACES_TABLE." order by id desc limit ".self::FACES_LIMIT);
        $query->execute();

        $rows = $query->fetchAll(PDO::FETCH_OBJ);

        $faces = [];

        foreach ($rows as $row) {
            $faces[$row->id] = new Face(
                (int)$row->race,
                (int)$row->emotion,
                (int)$row->oldness,
                (int)$row->id
            );
        }

        return $faces;
    }

    /**
     * @param  FaceInterface  $face
     * @param  FaceInterface  $compared
     * @return float
     */
    private function calculateSimilarity(FaceInterface $face, FaceInterface $compared): float
    {
        return sqrt(
            ($face->getRace() - $compared->getRace()) ** 2
            + ($face->getEmotion() - $compared->getEmotion()) ** 2
            + ($face->getOldness() - $compared->getOldness()) ** 2
        );
    }

    /**
     * @param  FaceInterface  $face
     * @param  int  $limit
     * @return array
     */
    private function getSimilarFaces(FaceInterface $face, int $limit): array
    {
        $similarity = [];

        foreach ($this->faces as $compared) {
            $similarity[$compared->getId()] = $this->calculateSimilarity($face, $compared);
        }

        asort($similarity);

        $similarity = array_slice($similarity, 0, $limit, true);

        return array_keys($similarity);
    }
}
