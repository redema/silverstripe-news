
<% if News %>
<% control News %>
<div class="NewsPageItem">
	<% include NewsPageThumbnail %>
	<h2 class="NewsPageTitle"><a href="$Link">$OneFieldFrom(SummaryTitle Title)</a></h2>
	<% include NewsPageMetadata %>
	<div class="NewsPageSummary">
		<p><% control OneFieldFrom(SummaryContent Content) %>$Summary(255)<% end_control %></p>
	</div>
	<p class="NewsPageReadMore"><a href="$Link"><% _t('NewsPage.ss.READMORE', 'Read more &raquo;') %></a></p>
	<div class="Clear"></div>
</div>
<% end_control %>
<% end_if %>

