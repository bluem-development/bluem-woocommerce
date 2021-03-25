
<div class="wrap">
    <h1>Verzoeken via Bluem</h1>
    <nav class="nav-tab-wrapper">
        <!-- <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view?');?>" class="nav-tab
        <?php if ($tab===null) {
            echo "nav-tab-active";
        } ?>
        ">
            Uitleg
        </a>

        <?php if(bluem_module_enabled('mandates')) { ?>

        <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view&tab=mandates');?>" class="nav-tab
            <?php if($tab==='mandates') { echo "nav-tab-active"; } ?>
            ">
            Digitaal Incassomachtigen (eMandates)
        </a>
        <?php } ?>

        <?php if(bluem_module_enabled('payments')) { ?>

        <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view&tab=payments');?>" class="nav-tab
            <?php if($tab==='payments') { echo "nav-tab-active"; } ?>
            ">
            iDEAL (ePayments)
        </a>
        <?php } ?>

        <?php if(bluem_module_enabled('idin')) { ?>

        <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view&tab=idin');?>" class="nav-tab
            <?php if($tab==='idin') { echo "nav-tab-active"; } ?>
            ">
            iDIN (Identity)
        </a>
        <?php } ?> -->

        <a href="<?php echo admin_url('options-general.php?page=bluem');?>" class="nav-tab">
            Algemene instellingen
        </a>
        
        <a href="mailto:d.rijpkema@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">Problemen,
            vragen of suggesties? Neem contact op via e-mail</a>
    </nav>



    <p>
    Hieronder vind je een overzicht van alle Bluem verzoeken die gemaakt zijn sinds de update 1.2.7:
</p>

<table class="table widefat">

<thead>
    <tr>
    <th>Gebruiker</th>
    <th>Verzoek</th>
    <th>Datum</th>
    <th>Status</th>
    <th></th>
    </tr>
</thead>
<tbody>


<?php foreach($requests as $r) {
    ?>
<tr>


    <td>
    <?php if(isset($users_by_id[$r->user_id])) {
        ?>
<a href="<?php echo admin_url("user-edit.php?user_id=".$r->user_id);?>" target="_blank">
        <?php
        echo $users_by_id[$r->user_id]->user_nicename;
        ?>
        </a>

        <?php 
    } else {
        echo "Gast/onbekend";
    } ?>
    </td>
    <td>
    <?php echo $r->type;?> #<?php echo $r->transaction_id;?><br>
    <?php echo $r->description;?>
    </td>
    <td>
        <?php $rdate = strtotime($r->timestamp); ?>
        <?php echo date("d-m-Y H:i:s",$rdate); ?>
    </td>
    <td><?php echo $r->status;?></td>
    <td></td>

</tr>


    <?php } ?>

</tbody>
</table>

</div>
<?php //bluem_generic_tabler($requests);?>

<?php

// var_dump($requests);

// var_dump($logs);

// var_dump($users);
?>
