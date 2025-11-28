<div class="opensciencebadges opensciencebadges-sm">
  {foreach from=$osbBadges item="badge"}
    {if $badge.desc|@trim}
      <div class="opensciencebadge">
        <img src="{$badge.url}" alt="{$badge.alt|escape|default:""}" />
        <p class="opensciencebadge-desc">
          {$badge.desc|strip_unsafe_html}
        </p>
      </div>
    {/if}
  {/foreach}
</div>
