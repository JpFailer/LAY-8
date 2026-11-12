<?php

namespace App\Services;

use App\Models\AporteBibliotecaModel;

/**
 * Servicio de dominio para consultas paginadas y estadísticas de la Biblioteca UMC.
 */
class BibliotecaService
{
    private AporteBibliotecaModel $aporteModel;

    public function __construct(?AporteBibliotecaModel $aporteModel = null)
    {
        $this->aporteModel = $aporteModel ?? new AporteBibliotecaModel();
    }

    /**
     * Obtiene aportes paginados junto con su total global y páginas totales.
     */
    public function obtenerAportesPaginados(
        ?string $categoria = null,
        ?string $busqueda = null,
        ?string $carrera = null,
        int $pagina = 1,
        int $porPagina = 18
    ): array {
        $pagina = max(1, $pagina);
        $porPagina = max(1, min(60, $porPagina));

        $builder = $this->aporteModel->construirBuilderFiltrado($categoria, $busqueda, $carrera);
        $totalFiltrados = (int) $builder->countAllResults(false);
        $totalPaginas = max(1, (int) ceil($totalFiltrados / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;

        $aportes = $builder->findAll($porPagina, $offset);

        return [
            'aportes'       => $aportes,
            'total'         => $totalFiltrados,
            'paginaActual'  => $pagina,
            'totalPaginas'  => $totalPaginas,
            'porPagina'     => $porPagina,
        ];
    }

    /**
     * Calcula conteos por categoría y carrera usando 2 consultas GROUP BY en lugar de 12 queries.
     */
    public function obtenerContadores(?string $carreraSeleccionada = null): array
    {
        $db = \Config\Database::connect();

        // 1. Conteo agrupado por categoría (respetando el filtro de carrera si aplica)
        $catBuilder = $db->table('aportes_biblioteca')
            ->select("COALESCE(NULLIF(categoria, ''), 'otros') AS categoria, COUNT(*) AS total", false)
            ->groupBy("COALESCE(NULLIF(categoria, ''), 'otros')", false);

        if ($carreraSeleccionada && $carreraSeleccionada !== 'todas') {
            $catBuilder->groupStart()
                ->where('carrera', $carreraSeleccionada)
                ->orWhere('carrera', 'GENERAL')
                ->groupEnd();
        }

        $conteos = [
            'todos'      => 0,
            'guias'      => 0,
            'ejercicios' => 0,
            'resumenes'  => 0,
            'otros'      => 0,
        ];

        foreach ($catBuilder->get()->getResultArray() as $row) {
            $cat = strtolower((string) ($row['categoria'] ?? 'otros'));
            $cnt = (int) ($row['total'] ?? 0);
            $conteos['todos'] += $cnt;
            if (isset($conteos[$cat])) {
                $conteos[$cat] += $cnt;
            } else {
                $conteos['otros'] += $cnt;
            }
        }

        // 2. Conteo agrupado por carrera
        $carRows = $db->table('aportes_biblioteca')
            ->select("COALESCE(NULLIF(carrera, ''), 'GENERAL') AS carrera, COUNT(*) AS total", false)
            ->groupBy("COALESCE(NULLIF(carrera, ''), 'GENERAL')", false)
            ->get()
            ->getResultArray();

        $conteosCarreras = [
            'todas'   => 0,
            'ING-INF' => 0,
            'ING-MAR' => 0,
            'ING-AMB' => 0,
            'ADM'     => 0,
            'TUR'     => 0,
            'GENERAL' => 0,
        ];

        foreach ($carRows as $row) {
            $cod = strtoupper((string) ($row['carrera'] ?? 'GENERAL'));
            $cnt = (int) ($row['total'] ?? 0);
            $conteosCarreras['todas'] += $cnt;
            if (isset($conteosCarreras[$cod])) {
                $conteosCarreras[$cod] += $cnt;
            } else {
                $conteosCarreras['GENERAL'] += $cnt;
            }
        }

        return [
            'conteos'         => $conteos,
            'conteosCarreras' => $conteosCarreras,
        ];
    }
}
