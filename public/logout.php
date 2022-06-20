<?php

require_once __DIR__ . '/../bootstrap.php';

logout();

header('Location: index.php', 302);
