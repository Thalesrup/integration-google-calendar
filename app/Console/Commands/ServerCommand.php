<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ServerCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'tchacakum {--host=localhost : aquele host sapeca, pode até ser 0.0.0.0} {--port=8000 : a maldita porta que irá rodar o servidor}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Inicia o servidor web';

    /**
     * Executa o comando.
     *
     * @return void
     */
    public function handle()
    {
        $host = $this->option('host') ?? 'localhost';
        $port = $this->option('port') ?? 8000;

        $this->info("Iniciando o servidor web em {$host}:{$port}...");

        exec("php -S {$host}:{$port} -t public");
    }
}
