<?php

# ěšččřžýáýů

# Calculate Moving Average (http://en.wikipedia.org/wiki/Moving_average) from given data and subset size.
# if samecount is true, number of elements returned is same as in data
# if samecount is false, number of elements returned is count(data)/subsetsize
function movingAverage($data, $subsetsize = 5, $samecount = true) {
    if (!is_array($data)) {
        return (null);
    }

    $subsetsize = (int) $subsetsize;
    if ($subsetsize < 1 || count($data) < $subsetsize) {
        return ($data);
    }

    # change tu numeric index array
    $nidata = array_values($data);

    $output = [];

    # Compute averages only for positions where a full window fits
    # Valid indices: 0 .. count($nidata) - $subsetsize
    $max = count($nidata) - $subsetsize;
    $prev = array_sum(array_slice($nidata, 0, $subsetsize)) / $subsetsize;
    $output[0] = $prev;
    for ($i = 1; $i <= $max; $i++) {
        $prev = $prev - $nidata[$i - 1] / $subsetsize + $nidata[$i + $subsetsize - 1] / $subsetsize;
        $output[$i] = $prev;
    }

    # If samecount=true, pad the output to the original length with the last average
    if ($samecount && count($output) < count($nidata)) {
        $last = end($output);
        while (count($output) < count($nidata)) {
            $output[] = $last;
        }
    }

    return ($output);
}
