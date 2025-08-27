<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;

class GenerateTicketCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:generate-csv {--count=1000 : Number of random tickets to export}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CSV file with random ticket UUIDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        
        $totalTickets = Ticket::count();
        $this->info("Total tickets in database: {$totalTickets}");
        
        if ($totalTickets === 0) {
            $this->error('No tickets found in database');
            return 1;
        }
        
        $rowsToSelect = min($count, $totalTickets);
        $this->info("Selecting {$rowsToSelect} random tickets");
        
        $tickets = Ticket::inRandomOrder()->limit($rowsToSelect)->pluck('uuid');
        
        $filename = 'ticket_uuids.csv';
        $file = fopen($filename, 'w');
        
        // Write header
        fputcsv($file, ['PURCHASE_TICKET_ID']);
        
        // Write ticket UUIDs
        foreach ($tickets as $uuid) {
            fputcsv($file, [$uuid]);
        }
        
        fclose($file);
        
        $this->info("Generated CSV file: {$filename} with {$tickets->count()} rows");
        
        return 0;
    }
}
