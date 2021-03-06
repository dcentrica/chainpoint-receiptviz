## What is this?

A standalone tool for producing graphical representations of version 3 [Chainpoint Proof](https://chainpoint.org) JSON-LD documents. It partially mimics the behaviour of the `parseBranches()` function
out of the [chainpoint-parse](https://github.com/chainpoint/chainpoint-parse) JS lib, with the added ability to produce `.png` or `.svg` visualisations too.

## Requirements

* [PHP7.0](https://secure.php.net/) or higher.
* [Graphviz](https://graphviz.org/).

## Installation

    #> composer require dcentrica/chainpoint-receiptviz-php

## Notes

There are several chainpoint specification versions, with a version 4 currently under development. This library only supports the current v3 standard.

## Usage

    #!/usr/bin/php
    <?php
    // Very basic usage
    require(realpath(__DIR__ . '/dcentrica-chainpoint-viz/src/Viz/ChainpointViz.php'));
    require(realpath(__DIR__ . '/dcentrica-chainpoint-viz/src/Viz/HashUtils.php'));

    $receipt = file_get_contents('chainpoint.json');

    $viz = new \Dcentrica\Viz\ChainpointViz();
    $viz->setChain('bitcoin');
    $viz->setReceipt($receipt);
    $viz->setFilename(realpath(__DIR__) . '/chainpoint.svg');
    // Create linked TXIDs (Only works for SVG output). Options are:
    // blockchain.com | explorer.bitcoin.com | blockexplorer.com | smartbit.com.au
    $viz->setExplorer('smartbit.com.au');
    $viz->visualize();

See the "examples" directory for this example and output.

## Credits

Thanks to the [Tierion](https://tierion.com/) team, especially for the [chainpoint-parse](https://github.com/chainpoint/chainpoint-parse) JS lib which led me to understand how
a chainpoint document is put together.
