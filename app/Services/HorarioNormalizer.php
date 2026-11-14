<?php

namespace App\Services;

/**
 * Utilidades puras de normalización para días, horas, códigos de materia,
 * secciones y períodos académicos de la UMC.
 */
final class HorarioNormalizer
{
    private const DIAS_ORDEN = [
        'Lunes'     => 1,
        'Martes'    => 2,
        'Miercoles' => 3,
        'Jueves'    => 4,
        'Viernes'   => 5,
        'Sabado'    => 6,
        'Domingo'   => 7,
    ];

    public static function normalizarDia(string $dia): ?string
    {
        $limpio = mb_strtolower(trim($dia), 'UTF-8');
        $limpio = strtr($limpio, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
        ]);

        return match ($limpio) {
            'lunes'     => 'Lunes',
            'martes'    => 'Martes',
            'miercoles' => 'Miercoles',
            'jueves'    => 'Jueves',
            'viernes'   => 'Viernes',
            'sabado'    => 'Sabado',
            'domingo'   => 'Domingo',
            default     => null,
        };
    }

    public static function ordenDia(string $dia): int
    {
        $canon = self::normalizarDia($dia);
        return $canon !== null ? (self::DIAS_ORDEN[$canon] ?? 99) : 99;
    }

    /**
     * Convierte una hora ('7:15', '07:15:00', '02:15 PM') a minutos desde 00:00.
     */
    public static function aMinutos(?string $hora): ?int
    {
        $raw = trim((string) $hora);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $raw, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                return ($h * 60) + $min;
            }
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return ((int) date('G', $ts) * 60) + (int) date('i', $ts);
    }

    /**
     * Devuelve la hora en formato estándar 'HH:MM:SS' o null si no es válida.
     */
    public static function normalizarHora(?string $hora): ?string
    {
        $mins = self::aMinutos($hora);
        if ($mins === null) {
            return null;
        }

        return sprintf('%02d:%02d:00', intdiv($mins, 60), $mins % 60);
    }

    public static function formatearHoraCorta(?string $hora): string
    {
        $mins = self::aMinutos($hora);
        if ($mins === null) {
            return substr(trim((string) $hora), 0, 5);
        }

        return sprintf('%02d:%02d', intdiv($mins, 60), $mins % 60);
    }

    public static function codigoMateriaCanonico(string $codigo): string
    {
        $clean = strtoupper(trim($codigo));
        $clean = (string) preg_replace('/[–—−‐‑‒―_]/u', '-', $clean);
        $clean = (string) preg_replace('/\s+/', '', $clean);

        if (preg_match('/^([A-Z]{2,6})-?(\d{3,4})$/', $clean, $m)) {
            return $m[1] . '-' . $m[2];
        }

        return $clean;
    }

    /**
     * @return list<string>
     */
    public static function variantesCodigoMateria(string $codigo): array
    {
        $raw = strtoupper(trim($codigo));
        $canon = self::codigoMateriaCanonico($raw);
        $sinGuion = str_replace(['-', '_', ' '], '', $canon);
        $conGuionBajo = str_replace('-', '_', $canon);

        return array_values(array_unique(array_filter([
            $raw,
            $canon,
            $sinGuion,
            $conGuionBajo,
        ], static fn (string $v): bool => $v !== '')));
    }

    /**
     * Normaliza códigos de sección entre el PDF de inscripción ('SVC-A', 'SVC-E-IM')
     * y los horarios CGD-001 ('A', 'A (OP)', 'E (IM)').
     */
    public static function normalizarSeccionCanonica(string $seccion): string
    {
        $clean = strtoupper(trim($seccion));
        $clean = (string) preg_replace('/[–—−‐‑‒―]/u', '-', $clean);
        $clean = (string) preg_replace('/\s+/', ' ', $clean);

        if (preg_match('/^SVC-([A-Z0-9]+)(?:\s*-\s*|\s*\(\s*|\s+)([A-Z]{2,4})\)?$/', $clean, $m)) {
            return $m[1] . ' (' . $m[2] . ')';
        }
        if (preg_match('/^SVC-([A-Z0-9]+)$/', $clean, $m)) {
            return $m[1];
        }
        if (preg_match('/^([A-Z0-9]+)(?:\s*-\s*|\s*\(\s*)([A-Z]{2,4})\)?$/', $clean, $m)) {
            return $m[1] . ' (' . $m[2] . ')';
        }

        return $clean;
    }

    /**
     * Devuelve niveles priorizados de variantes de sección:
     * tier 1: equivalentes exactos (con mención si existe),
     * tier 2: letra/código base sin mención (p.ej. 'E' para 'SVC-E-IM').
     *
     * @return array{exactas: list<string>, base: list<string>}
     */
    public static function variantesSeccionTiers(string $seccion): array
    {
        $raw = strtoupper(trim($seccion));
        $raw = (string) preg_replace('/[–—−‐‑‒―]/u', '-', $raw);
        $raw = (string) preg_replace('/\s+/', ' ', $raw);
        $canon = self::normalizarSeccionCanonica($raw);

        $exactas = [$raw, $canon];
        $base = [];

        if (preg_match('/^(?:SVC-)?([A-Z0-9]+)(?:\s*-\s*|\s*\(\s*|\s+)([A-Z]{2,4})\)?$/', $raw, $m)) {
            $letra = $m[1];
            $mencion = $m[2];
            $exactas[] = "{$letra} ({$mencion})";
            $exactas[] = "{$letra}({$mencion})";
            $exactas[] = "{$letra}-{$mencion}";
            $exactas[] = "SVC-{$letra}-{$mencion}";
            $base[] = $letra;
            $base[] = "SVC-{$letra}";
        } elseif (preg_match('/^SVC-([A-Z0-9]+)$/', $raw, $m)) {
            $exactas[] = $m[1];
        } elseif ($canon !== '' && strlen($canon) <= 3) {
            $exactas[] = "SVC-{$canon}";
        }

        $exactas = array_values(array_unique(array_filter($exactas, static fn (string $v): bool => $v !== '')));
        $base = array_values(array_diff(
            array_unique(array_filter($base, static fn (string $v): bool => $v !== '')),
            $exactas
        ));

        return ['exactas' => $exactas, 'base' => $base];
    }

    public static function inferirCarreraPorSeccion(string $seccion): ?string
    {
        $clean = strtoupper(trim($seccion));
        if (preg_match('/(?:-|\(|\b)(IM|OP)(?:\)|\b)/', $clean)) {
            return 'ING-MAR';
        }
        return null;
    }

    public static function familiaPeriodo(string $periodo): string
    {
        $clean = strtoupper(trim($periodo));
        if (preg_match('/^(\d{4})[-_\s]*(0?1|I)$/', $clean, $m)) {
            return $m[1] . '-I';
        }
        if (preg_match('/^(\d{4})[-_\s]*(0?2|II)$/', $clean, $m)) {
            return $m[1] . '-II';
        }
        if (preg_match('/^(\d{4})[-_\s]*(0?3|III)$/', $clean, $m)) {
            return $m[1] . '-III';
        }

        return $clean;
    }

    /**
     * @return list<string>
     */
    public static function variantesPeriodo(string $periodo): array
    {
        $raw = strtoupper(trim($periodo));
        $variants = [$raw];

        if (preg_match('/^(\d{4})[-_\s]*(0?1|I)$/', $raw, $m)) {
            $anio = $m[1];
            array_push($variants, "{$anio}-01", "{$anio}-I", "{$anio}-1");
        } elseif (preg_match('/^(\d{4})[-_\s]*(0?2|II)$/', $raw, $m)) {
            $anio = $m[1];
            array_push($variants, "{$anio}-02", "{$anio}-II", "{$anio}-2");
        } elseif (preg_match('/^(\d{4})[-_\s]*(0?3|III)$/', $raw, $m)) {
            $anio = $m[1];
            array_push($variants, "{$anio}-03", "{$anio}-III", "{$anio}-3");
        }

        return array_values(array_unique(array_filter($variants, static fn (string $v): bool => $v !== '')));
    }
}
