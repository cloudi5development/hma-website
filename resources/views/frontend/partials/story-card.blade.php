{{-- Single Student Success Story card. Shared by the home grid (≤4 stories)
     and the Swiper slider (>4). Expects $story = ['img_url','salary','name',
     'role','tone']. --}}
<div class="hm-story-wrap">
    <article class="hm-story hm-story--{{ $story['tone'] }}" tabindex="0">
        <span class="hm-story__bg" aria-hidden="true"></span>
        <img class="hm-story__img"
             src="{{ $story['img_url'] }}"
             alt="{{ $story['name'] }} — {{ $story['role'] }}" loading="lazy">
        <span class="hm-story__overlay" aria-hidden="true"></span>
        <div class="hm-story__content">
            <div class="hm-story__salary">
                <span class="hm-story__amount">₹{{ $story['salary'] }}</span>
                <span class="hm-story__lpa">LPA</span>
            </div>
            <div class="hm-story__name">{{ $story['name'] }}</div>
            <span class="hm-story__role">{{ $story['role'] }}</span>
        </div>
    </article>
</div>
