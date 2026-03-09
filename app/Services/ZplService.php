<?php

namespace App\Services;

/**
 * ZplService
 *
 * Generates ZPL label strings for Zebra printers.
 * Label templates are determined by the part number prefix.
 *
 * Supported prefixes:
 *   BL  → BlueLine (PureFlex)
 *   SLV → PTFE Bellow Liner (Ethylene)
 *   FA  → FlexArmor (Ethylene)
 *   FJ  → Flexijoint (Ethylene)
 */
class ZplService
{
    // Shared embedded GRF graphics
    private const GRF_CERTIFICATION_LOGOS = "^FO106,340^GFA,1221,3360,80,:Z64:eJzt1bFu3DgQBuBhWKg75gECMo9wpQMo1qvcI6RUAGFFw4VLv1AKGi629BvENFykzBgpwkLQ3D+Udr1BcnsJsECaqNByudK30nBmSPTn+HP89qOlQM5KMpLJMRXqMUWBSQjz2eSxYKI3s5NpLPg6mfmYt6EQ4RUjhbqJJngbImH8BTwGIOo1cg1vVq+ReNxL8CYjE4nQXD0j7ITh6QNJhOeqJ+q5n/DSxuTBlBAXz+a/w0VZvN4leMFGjzO+EoZHPKGQ4eHKc5ObJPCEmkSeBngTAJvhnZN67gIehv/v4Upvk109p+vUrp5heFgjeI167RGNzKfoGR6u9E0y+WvsMRUIT+fhfdQX/DnPFrvN1Ss7L8JL6nnCY6p3/x8e3im7YLPvEH7fNbgjzPaOyWyj7+HZm+rxF3jbxdO8vFOvr/HbexjSmLvixmv2YxScrtQTe1/I3ibfrh6ixk+I/y0WlJAt8LYXqxeevaBRzzK58b54SYKT3vEK2fqN1x71dMV7V/MlkeHXZeO2pQ+3OffhSu942VzmgTRufvF69Zj1Ifbe7c3qoRTh+ZrPTFqH3l3ygDSLbbAP6YwIXls9Vz2UhHr9N97Nw+o1EzzUrtbbRBaZEPCwbTBMOOlV8PCU7oJ3Xl49TH3voQnA2/WDJiJj1fO4rvXVa6R6xL65rl6qXovXfvbS0+rRqB5r/DA89LoJUWDG+lZPdt4D7zz5kRe03obqhXjoyep19X0Dltve1Xxmh3xm32MKKVZqPqf3az6jpOG11WvSgfdU8L75XaZh75nP1eu1Ptipt6+P9Hbn2bSsLzx76H3J6r3PWDL1Wg3n59oPBq1feJja16965tkLP/CSem/SgUeCX/Gj9hduCqb2/SUFeFlXsXpu5yXNP/XMY/XewnPqndErrJtNaH7eov+xLZhy8eV57X/JIQxojvy6erXeMFzyuebfYxrgdYmGoB7CVD1katD+DA9TTX639Gf1sL9c5jOn69touWBo+Z/SVu/qMev6hkjd173X6X6UXcT+wUY9u9s/UqP7kZPUidabfcjjjKGp/UA9uZEPixc+qYeb0Gpwv3rY37BjYsrs9je82TiTldjNz95c+xXX+EkUjR8C7raHXtTNA5W+eIR41P1XPSbsek7zsTdP8DDUPbYuoT+nzeqd5PDr518n9vzRq36/d3QP/XXvxXBaz5YTe3xiL5/IOzj+BdeEnig=:7ED1";

    private const GRF_PUREFLEX_LOGO = "^FO80,130^GFA,253,540,20,:Z64:eJy9z70OgjAQAOAjDrfhCxR8jZpg8FV8BEcnJOz4SLbp4OYzlDA4WjeMxnoHDEVnveEu+ZL7A/h95JYzRoqLHa2jtMbyyxTq0GQGEH2a6K2ZWj47OmxMVd/tFbrkzFZU3uHWVAfvvH4u2LKN0aq3VN5Mnca8V5tS4ZJ6hbQXFP0tyLaamM3ZktE8m4Qd23xqTWgQGIbzJLSDncjoFhGzFaZ0ONOezOvXYFm7J1MPMvqN7R/xBhV6dck=:B697";

    private const GRF_ETHYLENE_LOGO_SMALL = "^FO630,26^GFA,93,104,4,:Z64:eJxjYEAFzA8YGPh/MDDYyDEwmD9jYEj/zMCQZszAcMywgeH4RyB+2MBwLBGCQeJp0gwMBmwQ9SB9IP3YAAAFyxIO:480F";

    private const GRF_FJ_LEFT_LOGO = "^FO29,128^GFA,361,1776,16,:Z64:eJzt1D0OgkAQBeARY2KnFsbSxIto4QFs9jxQegxqGo+gR/AIlISKQhNCENxhY5x5bqDVxFeQbL4sDLM/eS6SEYWtyJVIDtsSvPi7z+/GmD3n4Dynd9jFkL2JtBfgKWnfaVfTrdekvSLtBXgGfgFPwXfgETiB63H4AHflRy7Wa3AuP0hcYutc/si4cH9tz2km+s++EuvDvgUPwdset+0JwEet9nGfpwM+NN/3faivt37f/6+l26afhHP/J8K7/p/dOPOsT8PP6dHF44P7A/0CnoJfwUvwGzju/wc4nh88X/qAhJ/nszlqVy/gtlWbTdCFXvfDctFl/p331y+7kbH3QyITEz0BGHzF4Q==:E909";

    private const GRF_FJ_RIGHT_LOGO = "^FO160,130^GFA,253,540,20,:Z64:eJy9z70OgjAQAOAjDrfhCxR8jZpg8FV8BEcnJOz4SLbp4OYzlDA4WjeMxnoHDEVnveEu+ZL7A/h95JYzRoqLHa2jtMbyyxTq0GQGEH2a6K2ZWj47OmxMVd/tFbrkzFZU3uHWVAfvvH4u2LKN0aq3VN5Mnca8V5tS4ZJ6hbQXFP0tyLaamM3ZktE8m4Qd23xqTWgQGIbzJLSDncjoFhGzFaZ0ONOezOvXYFm7J1MPMvqN7R/xBhV6dck=:B697";

    /**
     * Generate a ZPL label string for the given part number.
     *
     * @param  string $partNumber  Epicor part number (determines template by prefix)
     * @param  string $jobNumber   Epicor job number (printed on label)
     * @param  int    $quantity    Number of copies to print
     * @return string|null         ZPL string, or null if prefix is unrecognised
     */
    public function generate(string $partNumber, string $jobNumber, int $quantity): ?string
    {
        $p2 = strtoupper(substr($partNumber, 0, 2));
        $p3 = strtoupper(substr($partNumber, 0, 3));

        return match (true) {
            $p2 === 'BL'  => $this->blueLineLabel($partNumber, $jobNumber, $quantity),
            $p3 === 'SLV' => $this->ptfeBellowLinerLabel($partNumber, $jobNumber, $quantity),
            $p2 === 'FA'  => $this->flexArmorLabel($partNumber, $jobNumber, $quantity),
            $p2 === 'FJ'  => $this->flexijointLabel($partNumber, $jobNumber, $quantity),
            default       => null,
        };
    }

    /**
     * Human-readable label type name derived from part number prefix.
     */
    public function getLabelType(string $partNumber): string
    {
        $p2 = strtoupper(substr($partNumber, 0, 2));
        $p3 = strtoupper(substr($partNumber, 0, 3));

        return match (true) {
            $p2 === 'BL'  => 'BlueLine',
            $p3 === 'SLV' => 'PTFE Bellow Liner',
            $p2 === 'FA'  => 'FlexArmor',
            $p2 === 'FJ'  => 'Flexijoint',
            default       => 'Unknown',
        };
    }

    // -------------------------------------------------------------------------
    // Private label builders
    // -------------------------------------------------------------------------

    private function header(): string
    {
        return "^XA\n^MMT\n^PW838\n^LL432\n^LS0\n";
    }

    private function footer(string $partNumber, string $jobNumber, int $qty, int $partX = 80, int $partY = 230): string
    {
        return implode("\n", [
            "^FT600,260^A0N,30,30^FH\\^CI28^FD{$jobNumber}^FS^CI27",
            "^FT{$partX},{$partY}^A0N,84,42^FH\\^CI28^FD{$partNumber}^FS^CI27",
            "^LRY^FO6,6^GB819,0,100^FS^LRN",
            "^LRY^FO11,266^GB814,0,54^FS^LRN",
            "^PQ{$qty},0,1,Y",
            "^XZ",
        ]);
    }

    private function blueLineLabel(string $pn, string $job, int $qty): string
    {
        return $this->header()
            . "^FT250,90^A0N,80,100^FH\\^CI28^FDBlueLine^FS^CI27\n"
            . "^FT122,306^A0N,44,74^FH\\^CI28^FDwww.PureFlex.com^FS^CI27\n"
            . self::GRF_CERTIFICATION_LOGOS . "\n"
            . self::GRF_PUREFLEX_LOGO . "\n"
            . "^FO610,26^GFA,93,104,4,:Z64:eJxjYEAFzA8YGPh/MDDYyDEwmD9jYEj/zMCQZszAcMywgeH4RyB+2MBwLBGCQeJp0gwMBmwQ9SB9IP3YAAAFyxIO:480F\n"
            . $this->footer($pn, $job, $qty);
    }

    private function ptfeBellowLinerLabel(string $pn, string $job, int $qty): string
    {
        return $this->header()
            . "^FT40,90^A0N,80,100^FH\\^CI28^FDPTFE Bellow Liner^FS^CI27\n"
            . "^FT122,306^A0N,44,74^FH\\^CI28^FDwww.Ethylene.com^FS^CI27\n"
            . self::GRF_CERTIFICATION_LOGOS . "\n"
            . self::GRF_PUREFLEX_LOGO . "\n"
            . $this->footer($pn, $job, $qty);
    }

    private function flexArmorLabel(string $pn, string $job, int $qty): string
    {
        return $this->header()
            . "^FT230,90^A0N,80,100^FH\\^CI28^FDFlexArmor^FS^CI27\n"
            . "^FT122,306^A0N,44,74^FH\\^CI28^FDwww.Ethylene.com^FS^CI27\n"
            . self::GRF_CERTIFICATION_LOGOS . "\n"
            . self::GRF_PUREFLEX_LOGO . "\n"
            . $this->footer($pn, $job, $qty);
    }

    private function flexijointLabel(string $pn, string $job, int $qty): string
    {
        return $this->header()
            . "^FO211,24^GFA,637,4088,56,:Z64:eJzt1kuOgzAMANAgFlnmCByFo4Wj5Sj0BpFmwyITj52vqwFDmdVIRBXQlNfSxLGj1NOe9o+bibvdGgBWZV25DGralI7KbNQR8GABPzOpU8FC98C+C/QTzY0A/pIz+VWdpr7kQHb4vfiozRmAWNwiO1Azc1O674Ib0fnuZuxcsnOiG2Cxa3cWO90154A5CDq/pRN3NEfFpUuFzjU30N0+O48OH3xV+86+8h8qzil70X0xN+J1Hl4Nm+zmrzzwxakynecOmNN50pMLsptKgHQXsouyMyUgswvp9CcHsqMBaM5wt4huLAuuAjXGPZfipsWZP3bfGEiSo9Dac+HEqZJQfrv13u9Zf8/NsjsalzBv99wku/d55y5cjzPuTLzntOze1lGKz+rgg3Xb4zqMJ67kCe0+czWfWfe+3sMgO3jl/Nldzi+YXmTnmGP57MS1PG/X9/wZsFaIrtQVu+KB5euAIy24Vsdmn5yv9WHXUZRSfMZeN+cNz6weYekXXa3TOP8br38nru0LprQ6er3FTsm1fchEBZXV95BK/LGr+x6TXN9PnDgcTBzSnGgd37/kyTx0ltyCN46pkA9tv3TmVpp6WkcQ80euuoE5oU2UhI/2g0972tN6+wHam3V5:420C\n"
            . "^FT122,306^A0N,44,74^FH\\^CI28^FDwww.Ethylene.com^FS^CI27\n"
            . self::GRF_CERTIFICATION_LOGOS . "\n"
            . self::GRF_FJ_LEFT_LOGO . "\n"
            . self::GRF_FJ_RIGHT_LOGO . "\n"
            . self::GRF_ETHYLENE_LOGO_SMALL . "\n"
            . $this->footer($pn, $job, $qty, 160, 230);
    }
}
