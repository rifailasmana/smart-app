<?php

$schedule = [
    ['date' => '20260201', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260202', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260203', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    ['date' => '20260204', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    // 5 Libur
    ['date' => '20260206', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    ['date' => '20260207', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    ['date' => '20260208', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    ['date' => '20260209', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    ['date' => '20260210', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    ['date' => '20260211', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    // 12 Libur
    ['date' => '20260213', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    ['date' => '20260214', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260215', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260216', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260217', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    ['date' => '20260218', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    // 19 Libur
    ['date' => '20260220', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    ['date' => '20260221', 'type' => 'A',  'start' => '140000', 'end' => '220000'],
    ['date' => '20260222', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260223', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    ['date' => '20260224', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    ['date' => '20260225', 'type' => 'MD', 'start' => '110000', 'end' => '190000'],
    // 26 Libur
    ['date' => '20260227', 'type' => 'EM', 'start' => '080000', 'end' => '160000'],
    ['date' => '20260228', 'type' => 'A',  'start' => '140000', 'end' => '220000']
];

$icsContent = "BEGIN:VCALENDAR\nVERSION:2.0\nPRODID:-//Kikan Schedule//EN\nCALSCALE:GREGORIAN\nMETHOD:PUBLISH\n";

foreach ($schedule as $s) {
    // Event Masuk
    $icsContent .= "BEGIN:VEVENT\n";
    $icsContent .= "SUMMARY:Masuk Kerja Shift " . $s['type'] . " 🏥\n";
    $icsContent .= "DTSTART:" . $s['date'] . "T" . $s['start'] . "\n";
    $icsContent .= "DTEND:" . $s['date'] . "T" . $s['start'] . "\n"; 
    $icsContent .= "DESCRIPTION:Semangat kerjanya Kikan! Jangan lupa absen masuk.\n";
    $icsContent .= "BEGIN:VALARM\n";
    $icsContent .= "TRIGGER:-PT30M\n";
    $icsContent .= "ACTION:DISPLAY\n";
    $icsContent .= "DESCRIPTION:Siap-siap berangkat kerja!\n";
    $icsContent .= "END:VALARM\n";
    $icsContent .= "END:VEVENT\n";

    // Event Pulang
    $icsContent .= "BEGIN:VEVENT\n";
    $icsContent .= "SUMMARY:Pulang Kerja Shift " . $s['type'] . " 🏠\n";
    $icsContent .= "DTSTART:" . $s['date'] . "T" . $s['end'] . "\n";
    $icsContent .= "DTEND:" . $s['date'] . "T" . $s['end'] . "\n";
    $icsContent .= "DESCRIPTION:Alhamdulillah, waktunya istirahat. Jangan lupa absen pulang!\n";
    $icsContent .= "BEGIN:VALARM\n";
    $icsContent .= "TRIGGER:-PT0M\n";
    $icsContent .= "ACTION:DISPLAY\n";
    $icsContent .= "DESCRIPTION:Waktunya absen pulang!\n";
    $icsContent .= "END:VALARM\n";
    $icsContent .= "END:VEVENT\n";
}

$icsContent .= "END:VCALENDAR";

echo $icsContent;
?>
