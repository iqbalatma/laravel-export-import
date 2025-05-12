<?php

namespace Iqbalatma\LaravelExportImport;

enum ExportStatus {
    case ON_PROGRESS;
    case COMPLETED;
    case FAILED;
}
