{{-- XML sitemap body, rendered by Frontend\SitemapController.

     The XML prolog is NOT written here. Blade tokenises the template with
     token_get_all(), so on a server with PHP's short_open_tag enabled (the live
     host has it on) a literal prolog is read as an opening PHP tag and this
     view fails to compile with a ParseError. The controller prepends it to the
     rendered output instead — which also keeps the prolog on the very first
     line, where XML requires it. --}}
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
