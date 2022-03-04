<?php
  wp_enqueue_script('api');
  wp_enqueue_script('api-account');
  
  $matches = array(
    'process'  => array(
      'where' => "gs.recorded IS NULL"
      // 'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-07-09 00:00:00' AND gs.datetime <= '2021-07-20 23:59:59'" // 33
      // 'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-07-21 00:00:00' AND gs.datetime <= '2021-08-01 23:59:59'" // 33
      // 'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-08-03 00:00:00' AND gs.datetime <= '2021-08-16 23:59:59'" // 34
      // 'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-08-17 00:00:00' AND gs.datetime <= '2021-08-30 23:59:59'" // 33
      // 'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-08-30 00:00:00' AND gs.datetime <= '2021-09-13 23:59:59'" // 33
      // 'where' => "gs.recorded IS NULL AND gs.datetime >= '2021-09-13 00:00:00' AND gs.datetime <= '2021-09-26 23:59:59'" // 33
    ),
    // 'recorded' => array('where' => 'gs.recorded IS NOT NULL'),
  );
  
  $maps = array(
    'pay_canyon'           => 'Canyon',
    'Payload_Blue_Art'     => 'Canyon',
    'pay_launchpad'        => 'Launchpad',
    'Payload_Orange_Art'   => 'Launchpad',
    'dom_waterway'         => 'Waterway',
    'Domination_Yellow'    => 'Waterway',
    'cp_stadium'           => 'Stadium',
    'ControlPoint_Stadium' => 'Stadium',
    'dom_quarry'           => 'Quarry',
    'Domination_Grey'      => 'Quarry',
  );
?>
<style>
  table{width:100%;}
  
  button[data-text]:after{content:attr(data-text);}
  button.active[data-text]:after{content:attr(data-active);}
  
  .tml.inline .tml-field-wrap{flex:0 0 32%;}
  .tml.inline .tml-field-wrap.game_ids{flex:0 0 49%;}
  
  .tml.inline .tml-field-wrap.tml-submit-wrap{flex: 0 0 49%;margin-top:1em;}
  .tml.inline .tml-field-wrap.tml-submit-wrap button{border-width:1px;}
</style>
<div class="tml">
  <form class="tml inline" method="GET" data-init="init" data-endpoint="stats/match" data-callback="reload" data-confirm="false" novalidate="novalidate" accept-charset="utf-8">
    <div class="tml-alerts"><ul class="tml-messages"></ul></div>
    <div class="tml-field-wrap">
      <label class="tml-label" for="clan_a">Team A</label>
      <input class="tml-field" type="text" name="clan_a" value="" id="clan_a">
    </div>
    <div class="tml-field-wrap">
      <label class="tml-label" for="clan_b">Team B</label>
      <input class="tml-field" type="text" name="clan_b" value="" id="clan_b">
    </div>
    <div class="tml-field-wrap">
      <label class="tml-label" for="range_start">Date</label>
      <input class="tml-field" type="text" name="range_start" value="<?php echo date('Y-m-d') ?>" id="range_start">
    </div>
    <div class="tml-field-wrap game_ids">
      <label class="tml-label" for="game_ids">Session IDs</label>
      <input class="tml-field" type="text" name="game_ids" value="" placeholder="Separate with commas" id="game_ids">
    </div>
    <div class="tml-field-wrap tml-submit-wrap">
      <button name="submit" type="submit" class="tml-button btn--small">
        Lookup &rarr;
      </button>
    </div>
  </form>
</div>

<div class="events wp-block-group alignfull">
  <div class="events__wrapper wp-block-group__inner-container">
    <?php
      foreach ($matches as $status => $query) {
        $action = $status == 'recorded' ? $status : 'to ' . ucwords($status);
        $data   = $pxl->stats->games($query);
        $count  = count($data);
        $title  = $count > 1 ? 'Matches' : 'Match';
        
        if ( $status == 'recorded' ) $data = array_reverse($data);
        
        printf('<div class="events__container event__container--%s alignwide" data-title="%s %s %s">', $status, $count, $title, $action);
          foreach ($data as $matchID => $match) include(PARTIAL . '/match.php');
        echo '</div>';
      }
    ?>
    </div>
  </div>
</div>