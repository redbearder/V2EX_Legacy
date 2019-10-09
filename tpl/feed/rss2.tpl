<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="/xslt/rss2.xsl" media="screen"?>
<rss version="2.0"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title>{$feed_title}</title>
		<link>{$site_url}</link>
		<description>{$feed_description}</description>
		<category>{$feed_category}</category>
		<language>{$site_lang}</language>
{foreach from=$a_topics item=topic}
		<item>
{if $topic->tpc_posts eq 0}
			<title>{$topic->tpc_title} ... no reply</title>
{else}
{if $topic->tpc_posts > 1}
			<title>{$topic->tpc_title} ... {$topic->tpc_posts} replies</title>
{else}
			<title>{$topic->tpc_title} ... {$topic->tpc_posts} reply</title>
{/if}
{/if}
			<link>{$site_base}topic/view/{$topic->tpc_id}.html</link>
			<comments>{$site_base}topic/view/{$topic->tpc_id}.html#reply</comments>
			<dc:creator>{$topic->usr_nick}</dc:creator>
			<category>{$topic->nod_title}</category>
			<description>
			{$topic->tpc_content}
			</description>
			<pubDate>{$topic->tpc_pubdate}</pubDate>
			<guid>{$site_url}topic/view/{$topic->tpc_id}.html</guid>
		</item>
{/foreach}
	</channel>
</rss>