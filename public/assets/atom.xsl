<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="2.0" 
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
    xmlns:atom="http://www.w3.org/2005/Atom"
    exclude-result-prefixes="atom"
>
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <title><xsl:value-of select="/atom:feed/atom:title"/></title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <link href="/assets/semantic.min.css" rel="stylesheet" />
                <link href="/assets/style.css" rel="stylesheet" />
            </head>
            <body>
                <xsl:if test="/atom:feed/atom:author">
                    <div class="ui top fixed menu">
                      <a class="item" href="/"><img src="/assets/websub-rocks-icon.png" /></a>
                      <a class="item" href="/">Home</a>
                      <a class="item" href="/publisher">Publisher</a>
                      <a class="item" href="/subscriber">Subscriber</a>
                      <a class="item" href="/hub">Hub</a>
                      <a class="item" href="/dashboard">Dashboard</a>
                      <div class="right menu">
                        <span class="item"><xsl:value-of select="/atom:feed/atom:author"/></span>
                        <a class="item" href="/auth/signout">Sign Out</a>
                      </div>
                    </div>
                </xsl:if>
                <div class="single-column">
                    <xsl:if test="/atom:feed/atom:author">
                        <section class="content">
                          <p><![CDATA[Congrats! Now that your subscription is active, you can generate new posts that will be delivered to your subscriber! Click the button below to add a new post to this feed, and send a notification to subscribers of this feed.]]></p>
                          <a>
                            <xsl:attribute name="class">ui blue button</xsl:attribute>
                            <xsl:attribute name="href">
                                <xsl:value-of select="/atom:feed/atom:publishUrl"/>
                            </xsl:attribute>
                            Create New Post
                          </a>
                        </section>                      
                    </xsl:if>
                    <xsl:for-each select="/atom:feed/atom:entry">
                        <section class="content h-entry">
                            <xsl:if test="atom:content">
                                <div class="e-content p-name">
                                    <xsl:value-of select="atom:content" disable-output-escaping="yes"/>
                                </div>
                            </xsl:if>
                            <div class="p-author h-card">
                                <xsl:value-of select="atom:author/atom:name"/>
                            </div>
                            <a class="u-url">
                                <xsl:attribute name="href">
                                    <xsl:value-of select="atom:link/@href"/>
                                </xsl:attribute>
                                <time class="dt-published">
                                    <xsl:value-of select="atom:published"/>
                                </time>
                            </a>
                        </section>
                    </xsl:for-each>
                </div>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>