<a href="/admin/user/addGroup">Add Group</a>
<table>
<thead>
	<tr>
		<td style="width: 50px;">Id</td>
		<td style="width: 200px;">Name</td>
		<td style="width: 100px;">Modify</td>
	</tr>
</thead>
<tbody>
<?
foreach($groups as $g){
?>
	<tr>
		<td><?=$g['id'];?></td>
		<td><?=$g['name'];?></td>
		<td>
			<a href="/admin/user/editGroup/<?=$g['id'];?>/"><img src="/images/icon/edit.gif" /></a>
			<a href="/admin/user/delGroup/<?=$g['id'];?>"><img src="/images/icon/red_x.svg" height="14px"; /></a>
		</td>
	</tr>
<?
}

?>
</tbody>

</table>