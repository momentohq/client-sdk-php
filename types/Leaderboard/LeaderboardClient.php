<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Leaderboard;

/**
 * Like a sorted set, but for leaderboards!
 *
 * Elements in a leaderboard are keyed by an ID, which is an unsigned 64 bit integer.
 * Scores are single-precision floating point numbers.
 *
 * Each ID can have only 1 score.
 *
 * For batchy, multi-element apis, limits are 8192 elements per api call.
 *
 * Scores are IEEE 754 single-precision floating point numbers. This has a few
 * implications you should be aware of, but the one most likely to affect you is that
 * below -16777216 and above 16777216, not all integers are able to be represented.
 */
class LeaderboardClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Deletes a leaderboard. After this call, you're not incurring storage cost for this leaderboard anymore.
     * @param \Leaderboard\_DeleteLeaderboardRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteLeaderboard(\Leaderboard\_DeleteLeaderboardRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/DeleteLeaderboard',
        $argument,
        ['\Common\_Empty', 'decode'],
        $metadata, $options);
    }

    /**
     * Insert or update elements in a leaderboard. You can do up to 8192 elements per call.
     * There is no partial failure: Upsert succeeds or fails.
     * @param \Leaderboard\_UpsertElementsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpsertElements(\Leaderboard\_UpsertElementsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/UpsertElements',
        $argument,
        ['\Common\_Empty', 'decode'],
        $metadata, $options);
    }

    /**
     * Remove up to 8192 elements at a time from a leaderboard. Elements are removed by id.
     * @param \Leaderboard\_RemoveElementsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RemoveElements(\Leaderboard\_RemoveElementsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/RemoveElements',
        $argument,
        ['\Common\_Empty', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns the length of a leaderboard in terms of ID count.
     * @param \Leaderboard\_GetLeaderboardLengthRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetLeaderboardLength(\Leaderboard\_GetLeaderboardLengthRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/GetLeaderboardLength',
        $argument,
        ['\Leaderboard\_GetLeaderboardLengthResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Get a range of elements.
     * * Ordinal, 0-based rank.
     * * Range can span up to 8192 elements.
     * See RankRange for details about permissible ranges.
     * @param \Leaderboard\_GetByRankRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetByRank(\Leaderboard\_GetByRankRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/GetByRank',
        $argument,
        ['\Leaderboard\_GetByRankResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Get the rank of a list of particular ids in the leaderboard.
     * * Ordinal, 0-based rank.
     * @param \Leaderboard\_GetRankRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetRank(\Leaderboard\_GetRankRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/GetRank',
        $argument,
        ['\Leaderboard\_GetRankResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Get a range of elements by a score range.
     * * Ordinal, 0-based rank.
     *
     * You can request up to 8192 elements at a time. To page through many elements that all
     * fall into a score range you can repeatedly invoke this api with the offset parameter.
     * @param \Leaderboard\_GetByScoreRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetByScore(\Leaderboard\_GetByScoreRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/leaderboard.Leaderboard/GetByScore',
        $argument,
        ['\Leaderboard\_GetByScoreResponse', 'decode'],
        $metadata, $options);
    }

}
