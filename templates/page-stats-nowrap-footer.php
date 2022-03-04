<?php /* Template Name: Stats */
  global $wpdb;
  
  $season = isset($_GET['season']) ? $_GET['season'] : FALSE;
  
  $global  = $pxl->stats->global();
  $players = $pxl->stats->stats($season);
  
  $teams = array_column(get_posts(array(
    'post_type'      => 'team',
    'posts_per_page' => -1,
    'order'          => 'ASC',
    'orderby'        => 'title',
    'season'         => 'current',
  )), 'post_title', 'ID');
?>
<style>
  .sorting_1{background-color:rgba(0,20,50,0.1);color:#111;}
  .highlight{background-color:rgba(30,118,189,.8);color:#fff;}
  tr:hover .sorting_1{color:#fff;}
</style>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/sb-1.0.1/datatables.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/b-1.7.0/sb-1.0.1/datatables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.24/features/deepLink/dataTables.deepLink.min.js"></script>

<div class="content">
  <div class="alignfull">
    <table class="hide hover" id="datatable">
      <?php
        if ( !empty($players) ) {
          $modes = array(
            'Domination'    => array(),
            'Payload'       => array(),
            'Control Point' => array(),
          );
          
          $mode_fields = array('MP | Maps Played', 'Time', 'Score', 'Score/min');
          $headers     = array_keys($players[0]); array_pop($headers);
          
          foreach ($modes as $mode => $value) {
            foreach ($mode_fields as $field) {
              array_push($headers, "$mode $field");
            }
          }
        
          echo '<thead><th></th>';
            foreach ($headers as $header) {
              preg_match_all('#(?<=\s|\b)\pL#u', $header, $abbrv);
            
              $header  = explode(' | ', $header);
              $label   = $header[0];
              $tooltip = isset($header[1]) ? " title=\"{$header[1]}\"" : '';
            
              printf('<th%s>%s</th>', $tooltip, $label);
            }
          echo '</thead><tbody>';
          
          foreach ($players as $key => $player) {
            $modes = array(
              'Domination'    => array(),
              'Payload'       => array(),
              'Control Point' => array(),
            );
            
            $pos = $key + 1;
            $m   = explode(',', $player['modes']); unset($player['modes']);
            
            foreach ($m as $key => $md) {
              $md   = explode(':', $md);
              $k    = ucwords(str_replace('-', ' ', $md[0])); unset($md[0]);
              $data = array_combine($mode_fields, $md);
            
              foreach ($data as $key => $value) {
                $modes[$k][$key] = $value;
              }
            }
          
            foreach ($modes as $m => $mode) {
              if ( empty($mode) ) {
                $siblings = array_values(array_filter($modes));
                $mode = array_combine(
                  array_keys($siblings[0]),
                  array_fill(0,count($siblings[0]), 0)
                );
              }
            
              foreach ($mode as $t => $value) {
                $player["$m $t"] = $value;
              }
            }
          
            printf('<tr><td></td><td>%s</td></tr>', implode('</td><td>', $player));
          }
        }
      ?>
      </tbody>
    </table>
  </div>
  <script>
    var navbar = document.querySelector('.navbar'), table;
    
    navbar.classList.add('navbar--static');
    navbar.classList.remove('navbar--smart');
    
    document.addEventListener("DOMContentLoaded", function(event) {
      var searchOptions  = jQuery.fn.dataTable.ext.deepLink(['order', 'search.search']),
          defaultOptions = {
            buttons:['searchBuilder'],
            columnDefs: [
              { "searchable": false, "orderable": false, "targets": 0 },
              { "orderSequence": [ "desc", "asc" ], "targets": '_all' },
            ],
            dom: '<"filters"fBi><"stats"t><p>',
            language: {
              search: "_INPUT_",
              searchPlaceholder: 'Search',
              searchBuilder: {
                button: 'Filter Stats',
                title: 'Filters'
              }
            },
            order: [[ 1, 'asc' ]],
            paging: true,
            pageLength: 20,
            searchBuilder: {
              greyscale: true
            },
            initComplete: function( settings, json ) {
              jQuery('.hide').removeClass('hide');
            }
          };
          
      table = jQuery('#datatable').DataTable(jQuery.extend(defaultOptions, searchOptions));
      
      table.on('order.dt search.dt', function () {
        table.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
          cell.innerHTML = i+1;
        });
      }).draw();
      
      jQuery('#datatable tbody')
        .on( 'mouseenter', 'td', function () {
          var colIdx = table.cell(this).index().column;
          
          jQuery(table.cells().nodes()).removeClass( 'highlight' );
          jQuery(table.column(colIdx).nodes()).addClass( 'highlight' );
        });
    })
  </script>
</div>