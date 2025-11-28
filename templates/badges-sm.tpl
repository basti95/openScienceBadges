<div class="opensciencebadges opensciencebadges-sm">
  {foreach from=$osbBadges item="badge"}
    {if $badge.desc|@trim}
      <div class="opensciencebadge">
        <img src="{$badge.url}" alt="{$badge.alt|escape|default:""}" />
        <div class="opensciencebadge-desc">
          {$badge.desc|strip_unsafe_html}
        </div>
      </div>
    {/if}
  {/foreach}
</div>
