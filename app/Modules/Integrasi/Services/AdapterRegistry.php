<?php

namespace App\Modules\Integrasi\Services;

use App\Modules\Integrasi\Contracts\EmployeeSource;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/** Binding adaptor dari config/semangat.php — ganti adaptor tanpa mengubah modul lain. */
class AdapterRegistry
{
    public function __construct(private readonly Container $container) {}

    public function employeeSource(string $name): EmployeeSource
    {
        /** @var array<string, class-string<EmployeeSource>> $map */
        $map = config('semangat.adapters.employees', []);
        if (! isset($map[$name])) {
            throw new InvalidArgumentException("Adaptor pegawai tidak dikenal: {$name}");
        }

        return $this->container->make($map[$name]);
    }
}
