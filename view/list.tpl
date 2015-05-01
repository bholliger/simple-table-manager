<link type='text/css' href='<?php echo FILE_CSS ?>' rel='stylesheet' />
<div class='wrap'>
<h2>Simple Table Manager - List</h2>
<h3><?php echo $table_name ?></h3>
	
<?php 
	if ($key_word != "") {
		echo "<div class='updated'><p>Found " . number_format($total) . " results for: $key_word &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='" . $this->url['list'] . "'>Exit Search</a></p></div>";
	}
?>
	<div class='subsubsub'>
	<a href='<?php echo $this->url['add'] ?>'>Add New</a>&nbsp;&nbsp;
	<a href='<?php echo $this->url['list'] ?>&#038export'>Export CSV</a>
	</div>
	
	<form action='<?php echo $this->url['list'] ?>' method='post' name='search'>
	<p class='search-box'>
		<input type='search' name='search' placeholder='Search &hellip;' value='' />
	</p>
	</form>
		
	<?php if (empty($result)) { ?>
		<table class='wp-list-table widefat fixed'><tr><th>No results found.</th></tr></table>
			
	<?php } else { ?>
		<table class='wp-list-table widefat fixed'>
		<thead>
		<th></th>
<?php
		// column names
		$condition = array('search' => $key_word);
		foreach ($columns as $name => $type) {
			$condition['orderby'] = $name;
			if ($name == $order_by and "ASC" == $order) {
				echo "<th scope='col' class='manage-column sortable asc'  style=''>";
				$condition['order'] = 'DESC';
			} else {
				echo "<th scope='col' class='manage-column sortable desc'  style=''>";
				$condition['order'] = 'ASC';
			}
			echo "<a href='" . $this->url['list'] . "&#038;" . http_build_query($condition) . "'>";
			echo "<span>$name</span><span class='sorting-indicator'></span></a></th>";
		}
?>
		<tbody>
<?php
		$row_bgcolor = array('simple-table-manager-list-all-odd', 'simple-table-manager-list-all-even');	// decorate rows
		$row_bgcolor_index = 0;
		
		foreach ($result as $row ){
			echo "<tr>";
			foreach ($row as $k => $v) {
				if ($k == $primary_key) {
					echo "<td class='" . $row_bgcolor[$row_bgcolor_index] . "' nowrap><a href='" . $this->url['edit'] . '&#038id=' . $v . "'>Edit</a></td>";
				}
				echo "<td class='" . $row_bgcolor[$row_bgcolor_index] . "'>" . htmlspecialchars($v) . "</td>";
			}
			echo "</tr>";
			$row_bgcolor_index = ($row_bgcolor_index + 1) % count($row_bgcolor);
		}
?>
		</tbody>
		</thead>
		</table>
	
		<div class='tablenav bottom'>
		<div class='tablenav-pages'>
		<span class='displaying-num'>Total <?php echo number_format($total) ?></span>
		<span class='pagination-links'>
<?php
			// navigation
			$condition = array('search' => $key_word, 'orderby' => $orderby, 'order' => $order);
			$qry = http_build_query($condition);
			if (0 < $begin_row) {
				echo "<a title='first page' href='" . $this->url['list'] . "&#038beginrow=0&#038" . $qry . "'>&laquo;</a>";
				echo "<a title='previous page' href=" . $this->url['list'] . "&#038beginrow=". ($begin_row - $this->rows_per_page) . "&#038" . $qry . "'>&lsaquo;</a>";
			}else {
				echo "<a class='first-page disabled' title='first page'>&laquo;</a>";
				echo "<a class='prev-page disabled' title='previous page'>&lsaquo;</a>";
			}
			echo "<span class='paging-input'> " . number_format($begin_row + 1) . " - <span class='total-pages'>" . number_format($next_begin_row) . " </span></span>";
			if ($next_begin_row < $total) {
				echo "<a class='next-page' title='next page' href='" . $this->url['list'] . "&#038beginrow=$next_begin_row&#038" . $qry . "'>&rsaquo;</a>";
				echo "<a class='last-page' title='last page' href='" . $this->url['list'] . "&#038beginrow=$last_begin_row&#038" . $qry . "'>&raquo;</a>";
			}else {
				echo "<a class='next-page disabled' title='next page'>&rsaquo;</a>";
				echo "<a class='last-page disabled' title='last page'>&raquo;</a>";
			}
?>
		</span>
		</div><br class='clear' />
		</div>
	<?php } ?>
</div>