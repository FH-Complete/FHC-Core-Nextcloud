#!/bin/bash
#LVGROUP SYNC AND OE SYNC BOOTSTRAP SCRIPT - paralell sync run

#parts - number of group chunks to run in parallel
PARTS=4
#studiensemester - null if current
STUDIENSEMESTER="null"
SYNCUSERS=true
HOST="/var/www/html/fhcomplete/index.ci.php"
CONTROLLER="extensions/FHC-Core-Nextcloud/jobs/NextcloudSyncAll"

SYNCLVS=true
SYNCOES=true

if [ "$SYNCLVS" = true ]; then
        for i in `seq 1 $PARTS`
        do
                LVSYNCCALL="php $HOST $CONTROLLER runLvGroups/$STUDIENSEMESTER/$SYNCUSERS/$PARTS/$i &"
                eval $LVSYNCCALL
        done
        wait
        echo "all lv sync processes complete"
fi

if [ "$SYNCOES" = true ]; then
        for i in `seq 1 $PARTS`
        do
                OESYNCCALL="php $HOST $CONTROLLER runOeGroups/$SYNCUSERS/$PARTS/$i &"
                eval $OESYNCCALL
        done
        wait
        echo "all oe sync processes complete"
fi
