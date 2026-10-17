<?php

// routes/console.php
use Illuminate\Support\Facades\Schedule;

// Vérifier les documents expirant bientôt — chaque jour à 9h
Schedule::command('app:check-expiring-documents')->dailyAt('09:00');

// Vérifier les permis chauffeurs expirant + maintenance en retard
Schedule::command('alerts:check-expiring')->dailyAt('08:00');
