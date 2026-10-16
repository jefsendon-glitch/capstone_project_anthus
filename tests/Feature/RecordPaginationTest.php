<?php

use Illuminate\Pagination\LengthAwarePaginator;

test('record pagination keeps filters and enables only available directions', function (int $page, bool $previous, bool $next) {
    $records = new LengthAwarePaginator(collect(range(1, 5)), 15, 5, $page, [
        'path' => '/records', 'pageName' => 'records_page',
    ]);
    $records->appends(['search' => 'Round', 'status' => 'active'])->fragment('records');
    $html = $records->links()->toHtml();

    expect($html)->toContain('Previous', 'Next', 'Page '.$page.' of 3');
    expect(str_contains($html, 'rel="prev"'))->toBe($previous);
    expect(str_contains($html, 'rel="next"'))->toBe($next);
    expect($html)->toContain('search=Round', 'status=active', '#records');
})->with([[1, false, true], [2, true, true], [3, true, false]]);

test('a single page still shows disabled previous and next controls', function () {
    $html = (new LengthAwarePaginator(collect([1]), 1, 5))->links()->toHtml();

    expect($html)->toContain('Previous', 'Next', 'Page 1 of 1');
    expect(preg_match_all('/<button\b[^>]*\bdisabled(?:\s|=|>)/', $html))->toBe(2);
    expect($html)->not->toContain('rel="prev"', 'rel="next"');
});
