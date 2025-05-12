<?php

namespace Iqbalatma\LaravelExportImport;

enum ImportStatus {
    case ON_PROGRESS;
    case COMPLETED;
    case PARTIAL_COMPLETED;
    case FAILED;
}
