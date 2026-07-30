{{-- XML sitemap body. Rendered by Frontend\SitemapController with an
     application/xml content type — no HTML layout is involved.

     The <?xml ?> declaration is echoed rather than written literally: Blade
     compiles to PHP, and PHP's short_open_tag would otherwise swallow "<?xml"
     as an opening tag. --}}
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        <lastmod>{{ optional($url['lastmod'])->toAtomString() }}</lastmod>
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
    </url>
@endforeach
</urlset>
