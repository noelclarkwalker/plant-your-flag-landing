import AVFoundation
import Foundation

let repoRoot = URL(fileURLWithPath: "/Users/noelc/Documents/GitHub/plant-your-flag-landing")
let sourceURL = repoRoot.appendingPathComponent("assets/video/ps-heading-hawk.mp4")
let outputURL = repoRoot.appendingPathComponent("assets/video/ps-heading-hawk-silent.mp4")

try? FileManager.default.removeItem(at: outputURL)

let asset = AVURLAsset(url: sourceURL)
let wait = DispatchSemaphore(value: 0)

var videoTrack: AVAssetTrack?
var sourceDuration: CMTime = .zero

Task {
  videoTrack = try? await asset.loadTracks(withMediaType: .video).first
  sourceDuration = (try? await asset.load(.duration)) ?? .zero
  wait.signal()
}
wait.wait()

guard let videoTrack else {
  fputs("ERROR: No video track in source.\n", stderr)
  exit(1)
}

print(String(format: "Source duration: %.3f s", sourceDuration.seconds))

let composition = AVMutableComposition()
guard let compVideo = composition.addMutableTrack(
  withMediaType: .video,
  preferredTrackID: kCMPersistentTrackID_Invalid
) else {
  fputs("ERROR: Could not create composition video track.\n", stderr)
  exit(1)
}

let fullRange = CMTimeRange(start: .zero, duration: sourceDuration)
try compVideo.insertTimeRange(fullRange, of: videoTrack, at: .zero)

Task {
  compVideo.preferredTransform = (try? await videoTrack.load(.preferredTransform)) ?? .identity
  wait.signal()
}
wait.wait()

func export(preset: String, label: String) -> Bool {
  guard let session = AVAssetExportSession(asset: composition, presetName: preset) else {
    return false
  }
  session.outputURL = outputURL
  session.outputFileType = .mp4
  session.shouldOptimizeForNetworkUse = true

  let done = DispatchSemaphore(value: 0)
  session.exportAsynchronously { done.signal() }
  done.wait()

  if session.status == .completed {
    print("Export succeeded (\(label)).")
    return true
  }

  fputs("Export failed (\(label)): \(session.error?.localizedDescription ?? "unknown")\n", stderr)
  try? FileManager.default.removeItem(at: outputURL)
  return false
}

if export(preset: AVAssetExportPresetPassthrough, label: "passthrough") {
  exit(0)
}

if export(preset: AVAssetExportPreset1280x720, label: "1280x720") {
  exit(0)
}

fputs("ERROR: All export attempts failed.\n", stderr)
exit(1)
