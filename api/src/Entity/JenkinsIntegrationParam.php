<?php

declare(strict_types=1);

namespace App\Entity;

class JenkinsIntegrationParam extends AbstractIntegrationParam
{
    private ?string $folder = null;
    private ?string $jobName = null;
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $jobs = [];

    public function getType(): string
    {
        return 'jenkins';
    }

    public function getFolder(): ?string
    {
        return $this->folder;
    }

    public function setFolder(?string $folder): static
    {
        $this->folder = $folder !== null && trim($folder) !== '' ? self::normalizeFolder($folder) : null;

        return $this;
    }

    public function getJobName(): ?string
    {
        return $this->jobName;
    }

    public function setJobName(?string $jobName): static
    {
        $this->jobName = $jobName !== null && trim($jobName) !== '' ? trim($jobName) : null;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     */
    public function setJobs(array $jobs): static
    {
        $this->jobs = $jobs;

        return $this;
    }

    public function getJobsCount(): int
    {
        return count($this->jobs);
    }

    public function toArray(): array
    {
        $data = $this->extra;
        if ($this->folder !== null) {
            $data['folder'] = $this->folder;
        }
        if ($this->jobName !== null) {
            $data['job_name'] = $this->jobName;
            $data['job'] = $this->jobName;
        }
        if (!empty($this->jobs)) {
            $data['jobs'] = $this->jobs;
            $data['jobs_count'] = count($this->jobs);
        }

        return $data;
    }

    public static function fromArray(array $parameters): static
    {
        $param = new self();

        $folder = $parameters['folder'] ?? $parameters['folder_path'] ?? $parameters['job_folder'] ?? null;
        if ($folder !== null && trim((string) $folder) !== '') {
            $param->setFolder((string) $folder);
        }

        $name = $parameters['job_name'] ?? $parameters['job'] ?? null;
        if ($name !== null && trim((string) $name) !== '') {
            $param->setJobName((string) $name);
        }

        if (isset($parameters['jobs']) && is_array($parameters['jobs'])) {
            $param->setJobs($parameters['jobs']);
        }

        $extra = $parameters;
        unset($extra['folder'], $extra['folder_path'], $extra['job_folder'], $extra['job_name'], $extra['job'], $extra['jobs'], $extra['jobs_count']);
        $param->setExtra($extra);

        return $param;
    }

    public function validate(): array
    {
        $errors = [];
        if (($this->folder === null || $this->folder === '') && ($this->jobName === null || $this->jobName === '')) {
            $errors['folder'] = "Le dossier ou le nom du job Jenkins est requis.";
        }

        return $errors;
    }

    public function getTargetDisplay(): string
    {
        if ($this->folder !== null) {
            $count = count($this->jobs);
            if ($count > 0) {
                return sprintf('%s (%d job%s)', $this->folder, $count, $count > 1 ? 's' : '');
            }

            return $this->folder;
        }

        return $this->jobName ?? 'Non configuré';
    }

    public static function normalizeFolder(string $input): string
    {
        $path = trim($input);
        if (preg_match("#^https?://[^/]+(/?.*)$#i", $path, $m)) {
            $path = $m[1];
        }
        $path = str_replace([" » ", " > ", "»", ">"], "/", $path);
        $path = trim($path, "/");
        if ($path === "") {
            return "";
        }

        $parts = explode("/", $path);
        $segments = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === "" || $p === "job") {
                continue;
            }
            $segments[] = $p;
        }

        if (empty($segments)) {
            return "";
        }

        $folderPath = "";
        foreach ($segments as $seg) {
            $folderPath .= "job/" . $seg . "/";
        }

        return $folderPath;
    }
}
