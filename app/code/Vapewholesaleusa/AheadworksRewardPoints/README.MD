#VUS-322
- fixed deprecated functionality explode(',', $this->config->getBalanceUpdateActions()) where $this->config->getBalanceUpdateActions() is null

#VUS-439
- fixed Aheadworks/RewardPoints/Controller/Block/Render.php - Trying to access array offset on value of type null
