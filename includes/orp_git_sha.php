<?php
/**
 * orp_git_sha() — return ' [<short-sha>]' identifying the deployed
 * iannucci/openrepeater commit, or empty string if no source-of-truth
 * is available. Designed to be require_once'd from any footer/template.
 *
 * Lookup precedence:
 *   Layer 1: $DOCUMENT_ROOT/.git-sha — written at deploy time by the
 *            build script (install_orp_from_github), or by the dev-mode
 *            git post-merge/post-checkout hooks for in-place pulls.
 *            Cheapest, no FS scan dependencies.
 *   Layer 3: $DOCUMENT_ROOT/.git/HEAD — direct read of the current
 *            HEAD ref. Used when .git/ happens to be present but no
 *            stamp file exists (e.g., a manual `git clone` deploy that
 *            skipped install_orp_from_github).
 *   Else:    empty string — footer just omits the SHA.
 *
 * Returns leading space + '[sha]' so callers can concatenate cleanly:
 *     iannucci/openrepeater . orp_git_sha() . ' ver: ...'
 */
if (!function_exists('orp_git_sha')) {
    function orp_git_sha() {
        $root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
        if ($root === '') return '';

        // Layer 1 — the stamp file
        $stamp = $root . '/.git-sha';
        if (file_exists($stamp)) {
            $sha = trim(@file_get_contents($stamp));
            if ($sha !== '') {
                return ' [' . htmlspecialchars($sha, ENT_QUOTES, 'UTF-8') . ']';
            }
        }

        // Layer 3 — fall back to reading .git/HEAD
        $head_path = $root . '/.git/HEAD';
        if (file_exists($head_path)) {
            $head = trim(@file_get_contents($head_path));
            if (strpos($head, 'ref: ') === 0) {
                $ref_path = $root . '/.git/' . substr($head, 5);
                if (file_exists($ref_path)) {
                    $sha = trim(@file_get_contents($ref_path));
                    if (preg_match('/^[0-9a-f]{7,40}$/', $sha)) {
                        return ' [' . substr($sha, 0, 7) . ']';
                    }
                }
            } elseif (preg_match('/^[0-9a-f]{7,40}$/', $head)) {
                return ' [' . substr($head, 0, 7) . ']';
            }
        }
        return '';
    }
}
?>
