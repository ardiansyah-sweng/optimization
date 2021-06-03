<?php

class Random
{
    public static function weight($ranges)
    {
        $ff_team_composition = mt_rand($ranges['ff']['team_composition'] * 100, $ranges['max']  * 100) / 100;
        $ff_process = mt_rand($ranges['ff']['process'] * 100, $ranges['max']  * 100) / 100;
        $ff_environmental_factors = mt_rand($ranges['ff']['environmental_factors'] * 100, $ranges['max']  * 100) / 100;
        $ff_team_dynamics = mt_rand($ranges['ff']['environmental_factors'] * 100, $ranges['max']  * 100) / 100;

        $dff_expected_team_change = mt_rand($ranges['dff']['expected_team_change'] * 100, $ranges['max']  * 100) / 100;
        $dff_introduction_new_tools = mt_rand($ranges['dff']['introduction_new_tools'] * 100, $ranges['max']  * 100) / 100;
        $dff_vendor_defect = mt_rand($ranges['dff']['vendor_defect'] * 100, $ranges['max']  * 100) / 100;
        $dff_team_member_responsibility = mt_rand($ranges['dff']['team_member_responsibility'] * 100, $ranges['max']  * 100) / 100;
        $dff_personal_issue = mt_rand($ranges['dff']['personal_issue'] * 100, $ranges['max']  * 100) / 100;
        $dff_expected_delay = mt_rand($ranges['dff']['expected_delay'] * 100, $ranges['max']  * 100) / 100;
        $dff_expected_ambiguity = mt_rand($ranges['dff']['expected_ambiguity'] * 100, $ranges['max']  * 100) / 100;
        $dff_expected_change = mt_rand($ranges['dff']['expected_change'] * 100, $ranges['max']  * 100) / 100;
        $dff_expected_relocation = mt_rand($ranges['dff']['expected_relocation'] * 100, $ranges['max']  * 100) / 100;

        return [
            'ff_team_composition' => $ff_team_composition,
            'ff_process' => $ff_process,
            'ff_environmental_factors' => $ff_environmental_factors,
            'ff_team_dynamics' => $ff_team_dynamics,
            'dff_expected_team_change' => $dff_expected_team_change,
            'dff_introduction_new_tools' => $dff_introduction_new_tools,
            'dff_vendor_defect' => $dff_vendor_defect,
            'dff_team_member_responsibility' => $dff_team_member_responsibility,
            'dff_personal_issue' => $dff_personal_issue,
            'dff_expected_delay' => $dff_expected_delay,
            'dff_expected_ambiguity' => $dff_expected_ambiguity,
            'dff_expected_change' => $dff_expected_change,
            'dff_expected_relocation' => $dff_expected_relocation
        ];
    }
}

$population_size = 100;
$random_seeds = 30;

$ranges = [
    'ff' => [
        'team_composition' => 0.91,
        'process' => 0.89,
        'environmental_factors' => 0.96,
        'team_dynamics' => 0.85
    ],
    'dff' => [
        'expected_team_change' => 0.91,
        'introduction_new_tools' => 0.96,
        'vendor_defect' => 0.90,
        'team_member_responsibility' => 0.98,
        'personal_issue' => 0.98,
        'expected_delay' => 0.96,
        'expected_ambiguity' => 0.95,
        'expected_change' => 0.97,
        'expected_relocation' => 0.98,
    ],
    'max' => 1
];

$positions = [];
for ($i = 0; $i <= $random_seeds - 1; $i++) {
    for ($j = 0; $j <= $population_size - 1; $j++) {
        $positions[] = Random::weight($ranges);
    }
}
print_r($positions);

foreach ($positions as $position) {
    $data = array($position['ff_team_composition'], $position['ff_process'], $position['ff_environmental_factors'], $position['ff_team_dynamics'], $position['dff_expected_team_change'], $position['dff_introduction_new_tools'], $position['dff_vendor_defect'], $position['dff_team_member_responsibility'], $position['dff_personal_issue'], $position['dff_expected_delay'], $position['dff_expected_ambiguity'], $position['dff_expected_change'], $position['dff_expected_relocation']);
    $fp = fopen('seeds.txt', 'a');
    fputcsv($fp, $data);
    fclose($fp);
}
