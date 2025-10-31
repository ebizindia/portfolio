<?php
    //echo date('jS-F');;
    //print_r($this->base_template_data['events_summary']);

    // $today_birthday=0;
    // $today_birthday_count=0;
    // $today_anniversaryday=0;
    // $today_anniversaryday_count=0;
    // $recfirst_fullname='';
    // if($this->base_template_data['loggedindata'][0]['usertype']!="ADMIN"){
    //     $class="d-none";
    // }
?>
<style>
.contact_image img{
width: 20px;
height: auto;
}
.card_container .card_single {
  border-radius: 0.5rem;
  margin-right: 15px;
  margin-bottom: 10px;
  display: inline-block;
  
}
.card_container .card_single:nth-child(2n+1) a {
  background-color: #074d82;
  color: #fff;
  letter-spacing: 1.2px;
  font-weight: 600;
  border-radius: 0.5rem;
}
.card_container .card_single:nth-child(2n+1) a:hover{
background-color:#043c66;
}
.card_container .card_single:nth-child(2n) a {
  background-color: #3b82bf;
  color: #fff;
  letter-spacing: 1.2px;
  font-weight: 600;
  border-radius: 0.5rem;
}
.card_container .card_single:nth-child(2n) a:hover{
background-color:#2c6699;
}
.dashboard-rounded-square-btn {
  font-size: 1rem !important;
  color: #fff;
}
.card_single a {
  min-width: 160px;
  box-shadow: 0 0 0 1px #dddfe2;
  transition: .3s all ease-in-out;
  padding: 15px;
  vertical-align: middle;
  display: table-cell;
}
@media screen and (max-width:767px){
    .contact_image img{
    width: 32px;
    height: auto;
    margin-right: 40px !important;
    }
    .pointer.clickable-cell, .dashboard_mem_cnt a, .Birthdays-Anniversaries {
        font-size: 20px !important;
    }
    .instruction-option{
        font-size: 17px !important;
    }
}
@media (max-width:640px){
	.card_single a {
		width:100%;			
		display: block;
		font-size: 20px !important;
		padding: 10px;
	}
	.card_container .card_single{
		display:block;
		margin-right: 0px;
	}
}
</style>
<div class="row">
    <div class="col-12 mt-3 mb-2">
    <div class="">
        <div class="card">
            <div class="card-body">
                <div class="mt-2">
                    
                    <div class='alert alert-info' style="background:#d1ecf1;">
					
                        <h3>Welcome to <?php echo $this->base_template_data['app_disp_name']; ?></h3>
                        <p class="instruction-option" style="margin-bottom: 0;">Please choose an option from the menu.</p>
                    </div>
					<div class="mb-3">
						<div>														
							<div class="card_container">
                                <?php
                                if($this->body_template_data['usercls']->canAccessThisProgram('customers.php')){
                                ?>
								<div class="card_single">
									<a href="customers.php" class="action-button btn menu-button issue_button mb-3 dashboard-rounded-square-btn" type="submit" id="record-save-button">
										<span>Customers</span>
									</a>
								</div>

                                <?php
                                    }

                                    if($this->body_template_data['usercls']->canAccessThisProgram('documents.php')){
                                ?>

								<div class="card_single">
									<a href="documents.php" class="action-button btn menu-button issue_button mb-3 dashboard-rounded-square-btn" type="submit" id="record-save-button">
										<span>Documents</span>
									</a>
								</div>

                                <?php
                                    }

                                    if($this->body_template_data['usercls']->canAccessThisProgram('visit-reports.php')){
                                ?>
								
								<div class="card_single">							
									<a href="visit-reports.php" class="action-button btn menu-button issue_button mb-3 dashboard-rounded-square-btn" type="submit" id="record-save-button">
										<span>Visit Reports</span>
									</a>
								</div>
                                <?php
                                    }
                                    if($this->body_template_data['usercls']->canAccessThisProgram('travel-calendar.php')){

                                ?>

								<div class="card_single">							
									<a href="travel-calendar.php" class="action-button btn menu-button issue_button mb-3 dashboard-rounded-square-btn" type="submit" id="record-save-button">
										<span>Travel Calendar</span>
									</a>
								</div>

                                <?php
                                    }
                                ?>

							</div>
						</div>
					</div>
					<div class="col-sm-9 padding-none">
                        <div class="pull-left">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>