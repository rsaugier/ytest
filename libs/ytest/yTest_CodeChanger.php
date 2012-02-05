<?php

class yTest_CodeChanger {
    private $codeChangeQueue = array();

    public function enqueue($codeChange) {
        $codeChange->apply();
        $this->codeChangeQueue[] = $codeChange;
    }

    public function undoChange($change) {
        $change->undo();
        $index = array_search($change, $this->codeChangeQueue);
        unset($this->codeChangeQueue[$index]);
    }

    public function undoAll() {
        $rev = array_reverse($this->codeChangeQueue);
        foreach ($rev as $change) {
            $change->undo();
        }
        $this->codeChangeQueue = array();
    }
};

?>
