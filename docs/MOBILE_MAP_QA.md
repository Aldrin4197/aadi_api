# Mobile map and marker update

The map uses circular category glyphs matching the Flutter explorer's category mapping. Selected markers fill teal, grow their icon, gain a yellow ring, and appear above nearby markers. Enter and Space select a focused marker; selection updates the existing marker instead of replacing its focused DOM node.

Mobile search starts collapsed. Search can be expanded and closed without submitting; results are hidden while editing to keep fields accessible. Cards and comparison controls account for the bottom safe area. The viewport uses dynamic height, preserves pinch zoom, and allows display cutout padding. Phone landscape uses the compact layout too.

## Validation

- Production build: passed.
- Existing frontend tests: 9 passed.
- Chromium fixture using the actual Explore and DiscoveryMap components: 320×740, 390×844, 430×932, 844×390, and 1440×1000.
- Checked horizontal overflow, initial compact mobile search, 16px search input, hiding/restoring results while editing, keyboard marker selection, selected fill, and 44px markers.
- At 390px checked that selecting two comparison checkboxes enables Compare and positions the results above the comparison bar.
- Tiles were stubbed in browser checks; live tile availability was not tested.
- The changed Explore file merges cleanly with PR #37's version. This PR does not overlap PR #39's provider-verification files.

## Phone acceptance check before release

On an installed PWA on iOS and Android, check safe-area spacing, keyboard opening/closing, map pinch/pan, horizontal card swiping, marker taps, comparison, and rotation. Browser emulation does not validate native touch feel or installed-PWA behavior. No migration or environment changes are required.
