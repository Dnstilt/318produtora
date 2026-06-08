<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:cleanup-failed 
                            {--days=7 : Número de dias para manter jobs falhos}
                            {--force : Executar sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa jobs falhos antigos da tabela failed_jobs';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = (int) $this->option('days');
        $force = $this->option('force');
        
        $cutoffDate = now()->subDays($days);
        
        // Contar jobs que serão deletados
        $count = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->count();
        
        if ($count === 0) {
            $this->info('Nenhum job falho antigo para limpar.');
            return;
        }
        
        $this->info("Encontrados {$count} jobs falhos com mais de {$days} dias.");
        
        if (!$force && !$this->confirm("Deseja deletar {$count} jobs falhos?", false)) {
            $this->info('Operação cancelada.');
            return;
        }
        
        // Deletar jobs antigos
        $deleted = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoffDate)
            ->delete();
        
        Log::info('jobs.cleanup.failed', [
            'deleted_count' => $deleted,
            'cutoff_days' => $days,
            'cutoff_date' => $cutoffDate->toDateTimeString(),
        ]);
        
        $this->info("{$deleted} jobs falhos foram deletados com sucesso.");
        
        // Também limpar jobs muito antigos da tabela jobs (caso tenham ficado presos)
        $veryOldDate = now()->subDays(30);
        $stuckJobs = DB::table('jobs')
            ->where('available_at', '<', $veryOldDate)
            ->count();
            
        if ($stuckJobs > 0) {
            $this->warn("Encontrados {$stuckJobs} jobs presos na fila com mais de 30 dias.");
            
            if ($force || $this->confirm("Deseja limpar esses jobs presos também?", false)) {
                $cleared = DB::table('jobs')
                    ->where('available_at', '<', $veryOldDate)
                    ->delete();
                    
                $this->info("{$cleared} jobs presos foram limpos.");
            }
        }
    }
}