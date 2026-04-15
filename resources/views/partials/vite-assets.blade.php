@php
    $entries = $entries ?? ['resources/css/app.css', 'resources/js/app.js'];
    $useBuildOnly = (bool) config('app.vite_build_only', false);
    $manifestPath = public_path('build/manifest.json');
    $manifest = $useBuildOnly && file_exists($manifestPath)
        ? json_decode((string) file_get_contents($manifestPath), true)
        : null;

    $cssFiles = [];
    $jsFiles = [];

    if ($useBuildOnly && is_array($manifest)) {
        foreach ($entries as $entry) {
            $asset = $manifest[$entry] ?? null;
            if (!is_array($asset)) {
                continue;
            }

            $file = $asset['file'] ?? null;
            if (is_string($file) && $file !== '') {
                $target = '/build/' . ltrim($file, '/');
                if (str_ends_with($file, '.css')) {
                    $cssFiles[] = $target;
                } elseif (str_ends_with($file, '.js')) {
                    $jsFiles[] = $target;
                }
            }

            foreach (($asset['css'] ?? []) as $cssFile) {
                if (is_string($cssFile) && $cssFile !== '') {
                    $cssFiles[] = '/build/' . ltrim($cssFile, '/');
                }
            }
        }

        $cssFiles = array_values(array_unique($cssFiles));
        $jsFiles = array_values(array_unique($jsFiles));
    }
@endphp

@if(!$useBuildOnly || !is_array($manifest))
    @vite($entries)
@else
    @foreach($cssFiles as $href)
        <link rel="stylesheet" href="{{ $href }}">
    @endforeach

    @foreach($jsFiles as $src)
        <script type="module" src="{{ $src }}"></script>
    @endforeach
@endif
