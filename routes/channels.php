<?php

use Illuminate\Support\Facades\Broadcast;

// Finance private channel — authenticated users only
Broadcast::channel('finance', function ($user) {
    return $user !== null;
});

// Monitor channel — public, token is validated via URL (no user needed)
Broadcast::channel('monitor', function () {
    return true;
});

// Station channel — public (kassenseite can listen to own ticket updates)
Broadcast::channel('station.{stationId}', function () {
    return true;
});
