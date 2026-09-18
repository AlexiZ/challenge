<?php

namespace App\Service;

final class ReleaseInfo
{
    public readonly ?\DateTimeImmutable $date;
    public readonly ?string $hash;

    public function __construct(string $projectDir)
    {
        $dateFile = $projectDir.'/RELEASE_DATE';
        $this->date = is_file($dateFile)
            ? \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', trim(file_get_contents($dateFile)), new \DateTimeZone('UTC')) ?: null
            : null;

        $hashFile = $projectDir.'/REVISION';
        $this->hash = is_file($hashFile) ? trim(file_get_contents($hashFile)) : null;
    }
}
