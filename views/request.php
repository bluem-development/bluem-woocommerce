<style>
.request-body {
    padding:10pt;
     background-color:#fff;
     border:1px solid #ddd;
     margin:6px;
    margin-top:0;
}
.request-label {
    color:#aaa;
    display: inline-block;
    margin-right: 20px;
    width:120px;
}
</style>

<div class="wrap">
    <h1>
    <?php echo bluem_get_bluem_logo_html(48);?>
            Bluem &middot; Verzoekdetails
    </h1>


    <nav class="nav-tab-wrapper">
       


    <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view'); ?>" class="nav-tab">
    <span class="dashicons dashicons-arrow-left-alt"></span>         Terug naar verzoeken overzicht
        </a>
            <!-- <a href="#" class="nav-tab nav-active tab-active active" style="background-color: #eee;">Alle verzoeken</a> -->
        <a href="<?php echo admin_url('options-general.php?page=bluem');?>" class="nav-tab">
        <span class="dashicons dashicons-admin-settings"></span>
            Algemene instellingen
        </a>
    </nav>
<div class="request-body">
    <h2>Verzoek</h2>
    <p>
    <span class="request-label">
        Type:
    </span>
        <?php echo ucfirst($request->type);?>
    </p>
    <p>
    <span class="request-label">
        Omschrijving:
    </span>
        <?php echo $request->description;?>
    </p>
    <p>
    <span class="request-label">
    Transactienummer: 
    </span>
    <?php echo $request->transaction_id; ?>
    <?php if(isset($request->debtor_reference) && $request->debtor_reference !=="") {
        ?>
        <br>
        <span class="request-label">
Klantreferentie:
        </span>

        <?php 
        echo $request->debtor_reference; 
    } ?>
    </p>


    <p>
    <span class="request-label">
Gebruiker:
        </span>

    <?php 
    if (isset($request_author) && !is_null($request_author)) {
            ?>
<a href="<?php echo admin_url("user-edit.php?user_id=".$request->user_id); ?>" target="_blank">
        <?php
        echo $request_author->user_nicename; ?>
        </a>

        <?php
        } else {
            echo "Gast/onbekend";
        } ?>
        
        </p>
    <p>
    <span class="request-label">
    Datum: 
    </span>
    
        <?php $rdate = strtotime($request->timestamp); ?>
        <?php echo date("d-m-Y H:i:s", $rdate); ?>
    </p>
    

    
    <?php
        if (!is_null($request->order_id)) { 
            
            $order = new \WC_Order($request->order_id); 		
            
            ?>
            <p><span class="request-label">
            Bestelling:
            </span>
            <a href="<?php echo admin_url("post.php?post={$request->order_id}&action=edit"); ?>" target="_blank">
            <?php echo $request->order_id ?> (<?php echo wc_price($order->get_total());?>)
            </a>
            </p>
            <?php
        } ?>
    
    <p>
    <span class="request-label">
    Status: 
    </span>
    <?php bluem_render_request_status($request->status); ?>
    </p>
    
<?php
if(count($logs)>0) {
    ?>
<h4>Gebeurtenissen:
</h4>
<ul>

    <?php

    foreach($logs as $log) {
        $ldate = strtotime($log->timestamp); ?>
    <li>
    <span class="request-label">
        <?php echo date("d-m-Y H:i", $ldate); ?>
    </span>
        <?php echo $log->description;?>
    </li>
    <?php
}
 ?>

</ul>
 <?php 
}
?>
</div>
<?php bluem_render_footer(); ?>
</div>
