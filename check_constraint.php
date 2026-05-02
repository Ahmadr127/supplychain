<?php
$r = DB::select("SELECT pg_get_constraintdef(c.oid) as def FROM pg_constraint c JOIN pg_class t ON c.conrelid = t.oid WHERE t.relname = 'approval_requests' AND c.conname LIKE '%status%'");
foreach($r as $row) { echo $row->def . "\n"; }
if(empty($r)) { echo "No status constraint found\n"; }
