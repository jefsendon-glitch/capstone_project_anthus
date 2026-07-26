<?php

// Entry point for the vercel-php runtime. __DIR__ inside public/index.php
// still resolves relative to public/, so this just forwards the request
// through Laravel's normal front controller.
require __DIR__.'/../public/index.php';
