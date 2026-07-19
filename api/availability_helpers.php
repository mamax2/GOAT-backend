<?php


function parseAvailabilitiesList(?array $list): array
{
    if ($list === null || !is_array($list)) {
        return [];
    }

    $out = [];
    foreach ($list as $row) {
        if (!is_array($row)) {
            continue;
        }
        $parsed = parseSingleAvailabilityRow($row);
        if ($parsed !== null) {
            $out[] = $parsed;
        }
    }
    return $out;
}

function parseSingleAvailabilityRow(array $row): ?array
{
    $dateRaw = $row['date'] ?? $row['avail_date'] ?? '';
    $dateRaw = is_string($dateRaw) ? trim($dateRaw) : '';
    if ($dateRaw === '') {
        return null;
    }
    // Solo YYYY-MM-DD
    $dt = DateTime::createFromFormat('Y-m-d', $dateRaw);
    if (!$dt || $dt->format('Y-m-d') !== $dateRaw) {
        return null;
    }
    $availDate = $dateRaw;

    $start = normalizeTimeString($row['start_time'] ?? '');
    $end = normalizeTimeString($row['end_time'] ?? '');
    if ($start === null || $end === null) {
        return null;
    }
    if ($start >= $end) {
        return null;
    }

    return [
        'avail_date' => $availDate,
        'start_time' => $start,
        'end_time' => $end,
    ];
}

function normalizeTimeString($value): ?string
{
    if ($value === null) {
        return null;
    }
    $t = is_string($value) ? trim($value) : '';
    if ($t === '') {
        return null;
    }
    if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $t, $m)) {
        return null;
    }
    $h = (int) $m[1];
    $min = (int) $m[2];
    $s = isset($m[3]) ? (int) $m[3] : 0;
    if ($h > 23 || $min > 59 || $s > 59) {
        return null;
    }
    return sprintf('%02d:%02d:%02d', $h, $min, $s);
}
