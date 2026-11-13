<?php
/*
 * ສ່ວນ: ລະຫັດ Sec-MS-GEC ຂອງບໍລິການສຽງ Microsoft Edge (ແທນ Python edge-tts — ບໍ່ຕ້ອງໃຊ້ .py ແລ້ວ)
 * ແປງຈາກ edge_tts/drm.py (edge-tts 7.3.1, generate_sec_ms_gec_v2) ແບບແຖວຕໍ່ແຖວ: "<CS64 16 hex>-<SHA256 64 hex>"
 * ຈາກເວລາ (ປັດລົງທຸກ 5 ນາທີ). ກວດແລ້ວໃຫ້ຜົນກົງກັບ Python 25 ຄ່າ. ໃຊ້ໂດຍ api/tts/edge_tts.php.
 * ຖ້າວັນໜຶ່ງ Microsoft ປ່ຽນວິທີ (ສຽງໃໝ່ສ້າງບໍ່ໄດ້ ແຕ່ສຽງໃນ cache ຍັງຫຼິ້ນໄດ້) → ແປງຈາກ edge-tts ລຸ້ນໃໝ່ຄືນ.
 * ຕ້ອງເປັນ PHP 64-bit (ຄູນ 32-bit ຜ່ານ edgeTtsMul32 ເພື່ອບໍ່ໃຫ້ລົ້ນເປັນ float).
 */

const EDGE_TTS_TRUSTED_CLIENT_TOKEN = '6A5AA1D4EAFF4E9FB37E23D68491D6F4';
const EDGE_TTS_M = 0xFFFFFFFF;
const EDGE_TTS_CS64_ADJUSTMENT0 = 0xF2F90B1A;
const EDGE_TTS_CS64_ADJUSTMENT1 = 0x0B1A0000;
const EDGE_TTS_CS64_COEFFICIENTS = [[0xE82E91B1, 0xB32700D0], [0x44DE24C6, 0x88DFF51D], [0x3BEB65BC, 0x29514038], [0xC89C5933, 0xB328CBDF], [0x8D1EC211, 0xD405B4D5], [0x314EF5D5, 0x26E55F3C], [0xE92491B1, 0x607801C6], [0xF0A5B9D5, 0x82B2C1FB], [0x06064CF3, 0xFA51A3DA], [0xDA79FA60, 0x207CC464], [0xBED20560, 0x3C3C74F3], [0x89F9C9F8, 0xD2D14B61], [0x35FA38C6, 0xBB04A2C1], [0xCB49E3C6, 0xC5626167]];
const EDGE_TTS_CS64_FACTOR = 0x82893933;
const EDGE_TTS_SHA256_ADJUSTMENT = 0xC9DC06A7;
const EDGE_TTS_SHA256_BLOCK_CONSTANTS = [0x428A2F98, 0x71374491, 0xB5C0FBCF, 0xE9B5DBA5, 0x3956C25B, 0x59F111F1, 0x923F82A4, 0xAB1C5ED5, 0xD807AA98, 0x12835B01, 0x243185BE, 0x550C7DC3, 0x72BE5D74, 0x80DEB1FE, 0xC9DC06A7, 0xC19BF174, 0xE49B69C1, 0xEFBE4786, 0x0FC19DC6, 0x240CA1CC, 0x2DE92C6F, 0x4A7484AA, 0x5CB0A9DC, 0x76F988DA, 0x983E5152, 0xA831C66D, 0xB00327C8, 0xBF597FC7, 0xC6E00BF3, 0xD5A79147, 0x06CA6351, 0x14292967, 0x27B70A85, 0x2E1B2138, 0x4D2C6DFC, 0x53380D13, 0x650A7354, 0x766A0ABB, 0x81C2C92E, 0x92722C85, 0xA2BFE8A1, 0xA81A664B, 0xC24B8B70, 0xC76C51A3, 0xD192E819, 0xD6990624, 0xF40E3585, 0x106AA070, 0x19A4C116, 0x1E376C08, 0x2748774C, 0x34B0BCB5, 0x391C0CB3, 0x4ED8AA4A, 0x5B9CCA4F, 0x682E6FF3, 0x748F82EE, 0x78A5636F, 0x84C87814, 0x8CC70208, 0x90BEFFFA, 0xA4506CEB, 0xBEF9A3F7, 0xC67178F2];
const EDGE_TTS_SHA256_INITIAL = [0x6A09E667, 0xBB67AE85, 0x3C6EF372, 0xA54FF53A, 0x510E527F, 0x9B05688C, 0x1F83D9AB, 0x5BE0CD19];
const EDGE_TTS_SHA256_ROUND_CONSTANTS = [0x428A2F98, 0x71374491, 0xB5C0FBCF, 0xE9B5DBA5, 0x3956C25B, 0x59F111F1, 0x923F82A4, 0xAB1C5ED5, 0xD807AA98, 0x12835B01, 0x243185BE, 0x550C7DC3, 0x72BE5D74, 0x80DEB1FE, 0x9BDC06A7, 0xC19BF174, 0xE49B69C1, 0xEFBE4786, 0x0FC19DC6, 0x240CA1CC, 0x2DE92C6F, 0x4A7484AA, 0x5CB0A9DC, 0x76F988DA, 0x983E5152, 0xA831C66D, 0xB00327C8, 0xBF597FC7, 0xC6E00BF3, 0xD5A79147, 0x06CA6351, 0x14292967, 0x27B70A85, 0x2E1B2138, 0x4D2C6DFC, 0x53380D13, 0x650A7354, 0x766A0ABB, 0x81C2C92E, 0x92722C85, 0xA2BFE8A1, 0xA81A664B, 0xC24B8B70, 0xC76C51A3, 0xD192E819, 0xD6990624, 0xF40E3585, 0x106AA070, 0x19A4C116, 0x1E376C08, 0x2748774C, 0x34B0BCB5, 0x391C0CB3, 0x4ED8AA4A, 0x5B9CCA4F, 0x682E6FF3, 0x748F82EE, 0x78A5636F, 0x84C87814, 0x8CC70208, 0x90BEFFFA, 0xA4506CEB, 0xBEF9A3F7, 0xC67178F2];
const EDGE_TTS_SHA256_SCHEDULE = [0xEB24BF9E, 0x1558691F, 0xA0C09EE2, 0x0CFA32BA, 0xA622EE8C, 0xD1DD9491, 0xC0DD25A0, 0xF9E70AB6, 0xF0C31573, 0x7058CE4E, 0x7188E606, 0x00E5AB33, 0xD258B060, 0x72766564];
const EDGE_TTS_SHA256_SIGMA0_ADJUSTMENT = 0x059C0B80;
const EDGE_TTS_SHA256_SIGMA1_ADJUSTMENT = 0x000B92C0;
const EDGE_TTS_SHA256_STATE = [0xFD9FBEA2, 0x91243CE7, 0xBE499094, 0xCD2E1E0A, 0x6C006F49, 0x04BD35AB, 0xFEB0A7E4, 0x03EFFBD2];

// ຄູນສອງຄ່າ 32-bit ເອົາແຕ່ 32-bit ລຸ່ມ (ແຍກເປັນ 16-bit ກັນລົ້ນ 64-bit)
function edgeTtsMul32($a, $b) {
    $a &= EDGE_TTS_M; $b &= EDGE_TTS_M;
    return (($a * ($b & 0xFFFF)) + ((($a * ($b >> 16)) & 0xFFFF) << 16)) & EDGE_TTS_M;
}
function edgeTtsRol16($v) {
    $v &= EDGE_TTS_M;
    return (($v << 16) | ($v >> 16)) & EDGE_TTS_M;
}
function edgeTtsRotr($x, $n) {
    return (($x >> $n) | ($x << (32 - $n))) & EDGE_TTS_M;
}
function edgeTtsSigma0($v) {
    return (($v >> 7) ^ ($v << 25) ^ ($v >> 18) ^ ($v << 14) ^ ($v >> 3)) & EDGE_TTS_M;
}
function edgeTtsSigma1($v) {
    return (($v >> 17) ^ ($v << 15) ^ ($v >> 19) ^ ($v << 13) ^ ($v >> 10)) & EDGE_TTS_M;
}

function edgeTtsRounds(array $state, array $w, $first = 0) {
    [$a, $b, $c, $d, $e, $f, $g, $h] = $state;
    $k = $first === 14 ? EDGE_TTS_SHA256_BLOCK_CONSTANTS : EDGE_TTS_SHA256_ROUND_CONSTANTS;
    for ($i = $first; $i < 64; $i++) {
        $t1 = $h + (edgeTtsRotr($e, 6) ^ edgeTtsRotr($e, 11) ^ edgeTtsRotr($e, 25)) + ($g ^ ($e & ($f ^ $g))) + $k[$i] + $w[$i];
        $t2 = (edgeTtsRotr($a, 2) ^ edgeTtsRotr($a, 13) ^ edgeTtsRotr($a, 22)) + (($a & $b) | ($c & ($a | $b)));
        $h = $g; $g = $f; $f = $e;
        $e = ($d + $t1) & EDGE_TTS_M;
        $d = $c; $c = $b; $b = $a;
        $a = ($t1 + $t2) & EDGE_TTS_M;
    }
    return [$a, $b, $c, $d, $e, $f, $g, $h];
}

function edgeTtsExpand(array &$w, $first = 16) {
    for ($i = $first; $i < 64; $i++) {
        $x = $w[$i - 2]; $y = $w[$i - 15];
        $s1 = ($x >> 17) ^ ($x << 15) ^ ($x >> 19) ^ ($x << 13) ^ ($x >> 10);
        $s0 = ($y >> 7) ^ ($y << 25) ^ ($y >> 18) ^ ($y << 14) ^ ($y >> 3);
        $w[] = ($s1 + $w[$i - 7] + $s0 + $w[$i - 16]) & EDGE_TTS_M;
    }
}

function edgeTtsInit($data7) {
    $w = array_fill(0, 14, 0);
    $w[] = (ord($data7[0]) << 16) | (ord($data7[1]) << 8) | ord($data7[2]);
    $w[] = unpack('N', substr($data7, 3, 4))[1];
    for ($i = 16; $i < 30; $i++) {
        $v = EDGE_TTS_SHA256_SCHEDULE[$i - 16];
        if ($i - 2 >= 14) {
            $v += ($i === 16) ? (edgeTtsSigma1($w[$i - 2]) ^ EDGE_TTS_SHA256_SIGMA1_ADJUSTMENT) : edgeTtsSigma1($w[$i - 2]);
        }
        if ($i - 7 >= 14) $v += $w[$i - 7];
        if ($i - 15 >= 14) {
            $v += ($i === 29) ? (edgeTtsSigma0($w[$i - 15]) ^ EDGE_TTS_SHA256_SIGMA0_ADJUSTMENT) : edgeTtsSigma0($w[$i - 15]);
        }
        $w[] = $v & EDGE_TTS_M;
    }
    $w[] = (edgeTtsSigma1($w[28]) + $w[23] + edgeTtsSigma0($w[15]) + $w[14] + EDGE_TTS_SHA256_ADJUSTMENT - EDGE_TTS_SHA256_ROUND_CONSTANTS[14]) & EDGE_TTS_M;
    edgeTtsExpand($w, 31);
    $work = edgeTtsRounds(EDGE_TTS_SHA256_STATE, $w, 14);
    $out = [];
    foreach (EDGE_TTS_SHA256_INITIAL as $i => $x) $out[] = ($x + $work[$i]) & EDGE_TTS_M;
    return $out;
}

function edgeTtsDigest($data) {
    $len = 57 + strlen($data);
    $padded = substr($data, 7) . "\x80" . str_repeat("\x00", (((55 - $len) % 64) + 64) % 64) . pack('J', $len * 8);
    $state = edgeTtsInit(substr($data, 0, 7));
    for ($off = 0; $off < strlen($padded); $off += 64) {
        $w = array_values(unpack('N16', substr($padded, $off, 64)));
        edgeTtsExpand($w);
        $work = edgeTtsRounds($state, $w, 0);
        foreach ($state as $i => $x) $state[$i] = ($x + $work[$i]) & EDGE_TTS_M;
    }
    return pack('N8', ...$state);
}

function edgeTtsCs64($data, $digest) {
    [, $ka, $kb] = unpack('V2', $digest) + [0, 0, 0];
    $ka |= 1; $kb |= 1;
    $factors = [edgeTtsMul32($ka, EDGE_TTS_CS64_FACTOR), edgeTtsMul32($kb, EDGE_TTS_CS64_FACTOR)];
    $terms = []; $offsets = [];
    foreach (EDGE_TTS_CS64_COEFFICIENTS as $i => [$coef, $offset]) {
        $terms[] = edgeTtsMul32($coef, $factors[$i % 2]);
        $offsets[] = $offset;
    }
    $bytes = "\x00" . $data;
    $bytes .= str_repeat("\x00", (4 - strlen($bytes) % 4) % 4);
    $words = $bytes === '' ? [] : array_values(unpack('V*', $bytes));
    if (count($words) % 2) $words[] = 0;
    foreach ($words as $i => $word) {
        $term = edgeTtsMul32($word, $i % 2 === 0 ? $ka : $kb);
        $off = edgeTtsMul32(edgeTtsRol16($word), $i % 2 === 0 ? 0xEF0569FB : 0xC31713DB);
        if ($i === 0) {
            $term += edgeTtsMul32(EDGE_TTS_CS64_ADJUSTMENT0, $factors[0]);
            $off += EDGE_TTS_CS64_ADJUSTMENT1;
        }
        $terms[] = $term & EDGE_TTS_M;
        $offsets[] = $off & EDGE_TTS_M;
    }
    $swapAcc = $swapLast = $state = $acc = 0;
    for ($i = 0; $i < count($terms); $i += 2) {
        $x = $terms[$i]; $y = $terms[$i + 1];
        $t = edgeTtsRol16(($offsets[$i] + $x) & EDGE_TTS_M);
        $q = edgeTtsMul32($t, 0x76F50F34);
        $swapAcc += (edgeTtsMul32(edgeTtsRol16($q), 0xC3EFEA97) + $q) & EDGE_TTS_M;
        $t = edgeTtsRol16(($offsets[$i + 1] + $y) & EDGE_TTS_M);
        $q = edgeTtsMul32($t, 0x1881CE3C);
        $swapLast = (edgeTtsMul32(edgeTtsRol16($q), 0x35BD1EC9) + $q) & EDGE_TTS_M;
        $swapAcc = ($swapAcc + $swapLast) & EDGE_TTS_M;

        $state = (edgeTtsMul32($state, $ka) + $x) & EDGE_TTS_M;
        $state = edgeTtsMul32(edgeTtsRol16($state), 0xCF98B111);
        $state = edgeTtsMul32(edgeTtsRol16($state), 0x87085B9F);
        $state = edgeTtsMul32(edgeTtsRol16($state), 0x12CEB96D);
        $state = edgeTtsMul32(edgeTtsRol16($state), 0x257E1D83);
        $acc += $state;
        $t = (edgeTtsMul32($state, $kb) + $y) & EDGE_TTS_M;
        $t = edgeTtsMul32(edgeTtsRol16($t), 0xA27416F5);
        $t = edgeTtsMul32(edgeTtsRol16($t), 0xD38396FF);
        $state = edgeTtsMul32(edgeTtsRol16(edgeTtsMul32(edgeTtsRol16($t), 0x7C932B89)), 0xBFA49F69);
        $acc = ($acc + $state) & EDGE_TTS_M;
    }
    return [$swapLast ^ $state, $swapAcc ^ $acc];
}

// ລະຫັດ Sec-MS-GEC ຂອງເວລາ $unix (ວິນາທີ UTC, ລວມການແກ້ໂມງເຄື່ອງຄາດແລ້ວ)
function edgeTtsSecMsGec($unix) {
    $ticks = (int)floor($unix) + 11644473600;
    $ticks -= $ticks % 300;
    $ticks *= 10000000;
    $data = $ticks . EDGE_TTS_TRUSTED_CLIENT_TOKEN;
    $digest = edgeTtsDigest($data);
    $cs = edgeTtsCs64($data, $digest);
    return strtoupper(bin2hex(pack('V2', $cs[0], $cs[1]))) . '-' . strtoupper(bin2hex($digest));
}
