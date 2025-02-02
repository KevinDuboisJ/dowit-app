<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class TaskConfigurator extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Taakconfigurator';

    protected static ?int $navigationSort = 1;
}