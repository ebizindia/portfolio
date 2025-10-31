<?php 

	$action_mode = 'edit';  // This should be the default mode for both ADMIN and REGULAR members
	
	$show_contact_options = true;

	$is_member_admin = $this->body_template_data[$mode_index]['records'][$i_ul]['assigned_roles'][0]['role']=='ADMIN'?true:false; // the current member in the iteration
	$status_cls='';
	$admin_class= '';
	$associate_class= '';
	if($is_member_admin){
		$admin_class = 'admin_member';
	}
           
	$profile_pic = '';

	if($this->body_template_data[$mode_index]['records'][$i_ul]['profile_pic']!=''){
			$profile_pic = CONST_PROFILE_IMG_URL_PATH.$this->body_template_data[$mode_index]['records'][$i_ul]['profile_pic'] ;
	}else{
			if($this->body_template_data[$mode_index]['records'][$i_ul]['gender']==='F')
				$profile_pic = CONST_NOIMAGE_F_FILE ;
			else
				$profile_pic = CONST_NOIMAGE_M_FILE ;
	}


?>
<style>
.member_details_table{
	display:table;
	width:100%;
}
.member_details_left, .member_details_right{
	display: table-cell;
	text-align: left;	
}
.member_details_left{
	width:40%;
	vertical-align: middle;
}
.member_details_right{
	width:60%;
	vertical-align: top;
	padding-top:25px;
	padding-left:10px;
}
@media screen and (max-width: 1700px) {
  .gtc-lg-4 {
    grid-template-columns: 1fr 1fr 1fr !important;
  }
}
@media screen and (max-width: 1200px) {
  .gtc-lg-4 {
    grid-template-columns: 1fr 1fr !important;
  }
}
@media screen and (max-width: 915px) {
  .gtc-lg-4 {
    grid-template-columns: 1fr !important;
  }
}
@media screen and (max-width: 767px) {
  .gtc-lg-4 {
    grid-template-columns: 1fr 1fr !important;
  }
}
@media screen and (max-width: 680px) {
  .gtc-lg-4 {
    grid-template-columns: 1fr !important;
  }
}
</style>
<div class=" <?php echo $admin_class.'  '.$status_cls; if($action_mode){ echo 'member_list_block pointer clickable-cell  ';  }  ?> "  <?php if($action_mode){ ?> data-hash="<?php echo 'mode='.$action_mode.'&recid=',$this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>" <?php } ?>">
	<!--<div class="col-sm-12 col-md-12 col-lg-3">-->
	<div>
			<?php
			if($action_mode=='edit'){ 
			?>
			<div class="member_details_table">	
				<div class="member_img member_details_left">
					<a href="users.php#mode=edit&recid=<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"  data-in-mode="list-mode"    data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"   data-rel='tooltip' title="Edit details"  class="nopropagate"  >
						<img src="<?php echo $profile_pic; ?>" alt=""  class="prof_img"   >
					</a>
				</div>

				<div class="member_name member_details_right">
					<a href="users.php#mode=edit&recid=<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"     data-in-mode="list-mode"    data-recid="<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['id']; ?>"   data-rel='tooltip' title="Edit details"  class="nopropagate"   >
						<?php 
							echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['name'], true); 
							
						?>
					</a>
					<?php if(!empty($this->body_template_data[$mode_index]['records'][$i_ul]['user_group_name'])): ?>
						<div class="member_group_name" style="font-size: 12px; color: #666; margin-top: 3px; font-weight: normal;">
							<?php echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['user_group_name'], true); ?>
						</div>
					<?php endif; ?>
				
			<?php	
			}else{
			?>
				<div class="member_img">
					<img src="<?php echo $profile_pic; ?>" alt=""  class="prof_img"  >
				</div>

				<div class="member_name">
					<?php 
						echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['name'], true);
						if(!empty($this->body_template_data[$mode_index]['records'][$i_ul]['user_group_name'])): ?>
							<div class="member_group_name" style="font-size: 12px; color: #666; margin-top: 3px; font-weight: normal;">
								<?php echo \eBizIndia\_esc($this->body_template_data[$mode_index]['records'][$i_ul]['user_group_name'], true); ?>
							</div>
						<?php endif; 
					?>
				</div>

			<?php	
			}
			
			if($action_mode){ // IF action mode is not false then show the contact options
			
			

			
		?>
		<div class="member_details nopropagate" style="margin: 0 auto; margin-top: 8px;"   >
			<?php
				if(!empty($this->body_template_data[$mode_index]['records'][$i_ul]['email'])){
			?>
				<a href="mailto:<?php echo $this->body_template_data[$mode_index]['records'][$i_ul]['email']; ?>" style="white-space: nowrap;" class="nopropagate"><img src="images/email.png" alt="email" style="text-decoration: none;margin-right:15px;"></a>	
			<?php
				}
					if ( ($this->body_template_data[$mode_index]['cu_id'] == $this->body_template_data[$mode_index]['records'][$i_ul]['user_acnt_id']) || $this->body_template_data['cu_role'] == 'ADMIN' ) {

						if(!empty($this->body_template_data[$mode_index]['records'][$i_ul]['mobile'])){
							$whatsapp_num = $this->body_template_data[$mode_index]['records'][$i_ul]['mobile'];
							if(!preg_match("/^[+0]/", $whatsapp_num))
								$whatsapp_num = '+'.$this->body_template_data['country_code'].$whatsapp_num;
			?>

							<a href="https://wa.me/<?php echo $whatsapp_num; ?>" class="nopropagate" target="_blank" rel="noopener"><img src="images/whatsapp.png" alt="whatsapp" style="margin-right:15px;text-decoration: none;"></a>

							<a href="tel:<?php echo $whatsapp_num; ?>" style="white-space: nowrap;width: 100% !important;" class="nopropagate" rel="noopener"><img src="images/phone.png" alt="phone" style="margin-right:15px; text-decoration: none; "></a>
		
		<?php
						}
					}
		?>	
		</div>		
		<?php			
				 }
		?>													
																										
	</div>
</div>
</div>
</div>