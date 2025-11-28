<div class="opensciencebadges opensciencebadges-lg">
  <div class="opensciencebadges-lg-badges">
    {foreach from=$osbBadges item="badge"}
      {if $badge.desc|@trim}
        <img src="{$badge.url}" alt="{$badge.alt|escape|default:""}" />
      {/if}
    {/foreach}
  </div>
  <div class="opensciencebadges-lg-descs">
    {foreach from=$osbBadges item="badge"}
      {if $badge.desc|@trim}
        <div class="opensciencebadge-desc">
          <strong class="opensciencebadge-label">{$badge.name}</strong>
          {$badge.desc|strip_unsafe_html}
        </div>
      {/if}
    {/foreach}
  </div>
</div>
