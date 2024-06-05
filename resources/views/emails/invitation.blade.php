<div style="padding: 10% 10%;background-color: #eee;">
    <div style="padding: 20px;background-color: #fff">
        <div style="border-bottom: 1px solid grey; display: flex;">
        </div>
        <h4>Hello {{$user_name}},</h4>
        <div style="font-size:12px">
        <p>Welcome to Loyalty.</p>
        <p>Please login through below link.</p>
        <?php
        //   $registration_type =$mentor_user_name;
        $url = "http://16.16.79.221/login?type=" . urlencode($type) . "&email=" . urlencode($email) . "&user_name=" . urlencode($user_name);
        ?>
        <a href="<?php echo $url; ?>"><button type="submit" style="background-color: #3581ef;
    color: white;
    font-weight: bold;
    border-radius: 5px;
    border: none;
    padding: 5px 10px;
    cursor: pointer;">Complete your process</button></a>
        <br/><br/>
        Thank you.
        <br>
        <br/>
        <p>Best regards,<br>
            <span>Loyalty</span>  <br></p>
        </div>
    </div>
    <div style="text-align:center">
    <br>
        <p><span style="margin-left: 10px;font-size:12px">Powered by</span>
    <a href="">Loyalty</a></p><br>
</div>
</div>