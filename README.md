Sphinx-RT-Wrap-Class
====================

Library for a easy work with Sphinx RT indexes for PHP

## Entry ##

This class created for work with Sphinx (search engine) RT indexes through SphinxQL.

Supported operations:

- Insert
- Replace
- Update
- Delete
- Truncate
- Optimize
- Transactions (in automatic and manual modes)

**For searching use [Sphinx API](http://sphinxsearch.com/wiki/doku.php?id=php_api_docs)*

**Library tested on Sphinx 2.1.1. You may have problems during work with previous versions search engine. Method "truncate" works with Sphinx 2.1.1 and higher. In this case use "deleteAll".*

## Installation and usage ##

- Copy the file Sphinxrt.class.php to project directory;

- Include the file: (require_ince(‘path-to-class/Sphinxrt.class.php’));

- Initialize class ($sphinxrt = new Sphinxrt()).

## Main methods ##

<table>
	<thead>
		<tr>
			<td>Method</td>
			<td>Description</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>insert($index, $data)</td>
			<td>Inserts data into said index.</td>
		</tr>
		<tr>
			<td>replace($index, $data)</td>
			<td>Replaces data in said index.</td>
		</tr>
		<tr>
			<td>update($index, $data, $where)</td>
			<td>Updates data in said index. Method "update" can update only attributes of types:: int, bigint, float, MVA. I.e. attributes declared in configuration file as:: rt_attr_uint, rt_attr_bigint, rt_attr_float, rt_attr_multi, rt_attr_multi_64, rt_attr_timestamp. It's Sphinx features. Also there are problems with precision when updating float fields. For example: number 56.8 converts to 56.799999. When passing number without floating point or number with zero after floating point it will be converted to 0.000000. It's possible fix if convert a number to string with "000001" after floating point. Make sure that you really need this method before use it.</td>
		</tr>
		<tr>
			<td>delete($index, $ids)</td>
			<td>Deletes record/records from index.</td>
		</tr>
		<tr>
			<td>truncate($index)</td>
			<td>Clears data in index.</td>
		</tr>
		<tr>
			<td>deleteAll($index)</td>
			<td>Deletes all data from index.</td>
		</tr>
		<tr>
			<td>optimize($index)</td>
			<td>Optimizes index.</td>
		</tr>
		<tr>
			<td>errorMessage()</td>
			<td>Returns error message of last request. The method returns an empty string if there is no error.</td>
		</tr>
		<tr>
			<td>errorNumber()</td>
			<td>Returns error number of last request. The method returns 0 if there is no error.</td>
		</tr>
	</tbody>
</table>

## Arguments description ##

<table>
	<thead>
		<tr>
			<td>Argument</td>
			<td>Type</td>
			<td>Description</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>$index</td>
			<td>string</td>
			<td>Index name, declared in Sphinx configuration file.<br/>For example: news_rt.</td>
		</tr>
		<tr>
			<td>$data</td>
			<td>array</td>
			<td>Array of couples key-value. Where key is field in table.<br/>For example:<br/><br/>
array(<br/>
&nbsp;&nbsp;'author_id' => 1,<br/>
&nbsp;&nbsp;'title' => 'New title'<br/>
);<br/><br/>
You can use multi inserting and multi replacing of data.<br/>For example:<br/><br/>
array(<br/>
&nbsp;&nbsp;array(<br/>
&nbsp;&nbsp;&nbsp;&nbsp;'author_id' => 1,<br/>
&nbsp;&nbsp;&nbsp;&nbsp;'title' => 'New title'<br/>
&nbsp;&nbsp;),<br/>
&nbsp;&nbsp;array(<br/>
&nbsp;&nbsp;&nbsp;&nbsp;'author_id' => 2,<br/>
&nbsp;&nbsp;&nbsp;&nbsp;'title' => 'New title 2'<br/>
&nbsp;&nbsp;)<br/>
);
</td>
		</tr>
		<tr>
			<td>$where</td>
			<td>array</td>
			<td>Array of criterias for records selection. The parameter description is <a href="#the-parameter-of-criterias-where">here</a>.</td>
		</tr>
		<tr>
			<td>$ids</td>
			<td>integer or array</td>
			<td>Selection criterias for commands, supporting work only with id field (DELETE). The parameter may be record identificator or array of records identificators.<br/>For example:<br/><br/>
8<br/>
array(8, 9, 10);
</td>
		</tr>
	</tbody>
</table>

## The parameter of criterias (where) ##

This parameter is array of couples key-value, for example:

    // Select records where category_id =1
    array(
    	'category_id' => 1
    );

Key may contains operators: : =, !=, >, <, <>, >=, <=, IN, NOT IN, MATCH.

In case using operator IN or NOT IN, value must be array, for example:

    array(
    	'category_id IN' => array(2, 3, 8)
    );

In case using operator MATCH, value must be array containing keywords:

    array(
    	// ! All keywords in one array element
    	'MATCH' => array('office ipad')
    );

## Methods of work with transactions ##

<table>
	<thead>
		<tr>
			<td>Method</td>
			<td>Description</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>transBegin()</td>
			<td>Starts transaction.</td>
		</tr>
		<tr>
			<td>transStatus()</td>
			<td>Returns true, if transaction has no errors. Otherwise returns false.</td>
		</tr>
		<tr>
			<td>transCommit()</td>
			<td>Automatic completion of transaction. This method commits changes, if transaction has no errors. Otherwise method rollbacks all changes*. Returns true or false in case success or error respectively.</td>
		</tr>
		<tr>
			<td>transRollback()</td>
			<td>Rollbacks all changes in current transaction*.</td>
		</tr>
		<tr>
			<td>transComplete()</td>
			<td>Commits all changes in current transaction.</td>
		</tr>
	</tbody>
</table>
**Transactions doesn't work with methods "update" and "truncate" (It's Sphinx features).*

## Example of usage ##

Let’s consider an examples.

Structure of news table:

    CREATE TABLE IF NOT EXISTS `news` (
      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `author_id` bigint(20) unsigned NOT NULL,
      `category_id` tinyint(3) unsigned NOT NULL,
      `title` varchar(100) NOT NULL,
      `junk_field` tinyint(1) unsigned DEFAULT '1',
      `text` text NOT NULL,
      `created_at` int(11) unsigned NOT NULL,
      `views` bigint(20) NOT NULL DEFAULT '0',
      `rating` float unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

Example of Sphinx index configuration:

    index news_rt
    {
    	type = rt
    	path = C:/WebServer/Sphinx/data/news/news/news
    	rt_field	= title
    	rt_field	= text
    	
    	rt_attr_uint = author_id
    	rt_attr_uint = category_id
    	rt_attr_uint = views
    	rt_attr_timestamp = created_at
    	rt_attr_string = title
    	rt_attr_float = rating
    
    	docinfo  = extern
    	morphology = stem_enru
    	min_word_len = 1
    	html_strip = 1
    	charset_type = utf-8
    	enable_star = 1
    	rt_mem_limit = 64M
    }

*Table structure differ from index structure. For example, index doesn't contains "junk_field". It's made for demonstration a little library feature. User can pass full data to methods without filtering. The library independently detects fields necessary. In "update" method, the library detects attributes of supported types and updates only them. (Use "replace" for updating not only attributes, but also fields.)*

**The following examples may not have practical value. They only demonstrate library work.*

**$news – array of news.*

Let's append all news:

    $sphinxrt->insert('news_rt', $news);

Let's append 4th news:

    $sphinxrt->insert('news_rt', $news[3]);

Let's set 1000 views to 5th news:

    $sphinxrt->update('news_rt', array('views' => 1000), array('id' => 5));

Let's delete 8th news:

    $sphinxrt->delete('news_rt', 8);

Let's delete 2nd, 5th and 7th news:

    $sphinxrt->delete('news_rt', array(2, 5, 7));

Let’s consider an examples of successful transaction (changes will be committed):

    $sphinxrt->transBegin();
    $sphinxrt->insert('news_rt', $news[5]);
    $sphinxrt->delete('news_rt', 8);
    $sphinxrt->transComplete();

Let’s consider an examples of wrong transaction (changes will not be committed):

    // First, let's insert only 5th record
    // Then let's try insert all records
    // During inserting 5th record, we will get error: duplicate id '5'
    // After checking transaction status, we can  make rollback inserting previous  4 records

    $sphinxrt->insert('news_rt', $news[4]);
    $sphinxrt->transBegin();
    $sphinxrt->insert('news_rt', $news);
    if ($sphinxrt->transStatus())
    {
    	$sphinxrt->transCommit();
    }
    else
    {
    	$sphinxrt->transRollback();
    }


